<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserWardrobeRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AIRecommendationService
{
    private $httpClient;
    private $logger;
    private $apiKey;
    private $userWardrobeRepository;
    
    public function __construct(
        HttpClientInterface $httpClient, 
        LoggerInterface $logger,
        UserWardrobeRepository $userWardrobeRepository = null,
        string $apiKey = null
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $apiKey;
        $this->userWardrobeRepository = $userWardrobeRepository;
    }
    
    public function analyzeImage(?string $uploadDir = null, ?string $newFilename = null, ?string $imageContent = null): array
    {
        if (!$imageContent && $uploadDir && $newFilename) {
            $imageFile = $uploadDir . $newFilename;
            $imageContent = file_get_contents($imageFile);
        }

        if (!$imageContent) {
            throw new \InvalidArgumentException('Aucun contenu d\'image fourni');
        }
        
        $imageBase64 = base64_encode($imageContent);
        
        $prompt = "Examine cette image de vêtement en détail.\n" .
            "Identifie tous les vêtements et accessoires présents et renvoie un JSON structuré.\n" .
            "Pour chaque élément, indique son nom complet (type + caractéristiques + couleur), sa catégorie principale, et si possible sa marque.\n" .
            "Exemple de format attendu :\n" .
            "{\n" .
            "  \"items\": [\n" .
            "    {\n" .
            "      \"name\": \"Robe Longue Rouge Brodée\",\n" .
            "      \"category\": \"robe\",\n" .
            "      \"color\": \"rouge\",\n" .
            "      \"brand\": \"Unknown\"\n" .
            "    },\n" .
            "    {\n" .
            "      \"name\": \"Veste en Jean Délavé\",\n" .
            "      \"category\": \"veste\",\n" .
            "      \"color\": \"bleu\",\n" .
            "      \"brand\": \"Unknown\"\n" .
            "    }\n" .
            "  ]\n" .
            "}\n" .
            "Fournis uniquement le JSON, sans texte additionnel.";
        
        $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01'
            ],
            'json' => [
                'model' => 'claude-3-7-sonnet-20250219',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $prompt
                            ],
                            [
                                'type' => 'image',
                                'source' => [
                                    'type' => 'base64',
                                    'media_type' => 'image/jpeg',
                                    'data' => $imageBase64
                                ]
                            ]
                        ]
                    ]
                ],
                'max_tokens' => 1000
            ]
        ]);
        
        $responseData = json_decode($response->getContent(), true);
        $textResponse = $responseData['content'][0]['text'];
        
        $detectedItems = $this->extractItemsFromResponse($textResponse);
        
        return [
            'image_path' => $uploadDir ? $uploadDir . $newFilename : null,
            'detected_items' => $detectedItems
        ];
    }

    private function extractItemsFromResponse(string $response): array
    {
        if (preg_match('/\{[\s\S]*\}/m', $response, $matches)) {
            $jsonString = $matches[0];
            $data = json_decode($jsonString, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['items']) && is_array($data['items'])) {
                return $data['items'];
            }
            
            if (json_last_error() === JSON_ERROR_NONE) {
                $items = [];
                
                foreach ($data as $key => $value) {
                    if (is_array($value) && isset($value['name'])) {
                        $items[] = $value;
                    } elseif (is_array($value) && (isset($value['Nom']) || isset($value['nom']))) {
                        $items[] = [
                            'name' => $value['Nom'] ?? $value['nom'] ?? '',
                            'category' => $value['Category'] ?? $value['category'] ?? $value['Categorie'] ?? $value['categorie'] ?? '',
                            'color' => $value['Color'] ?? $value['color'] ?? $value['Couleur'] ?? $value['couleur'] ?? '',
                            'brand' => $value['Brand'] ?? $value['brand'] ?? $value['Marque'] ?? $value['marque'] ?? 'Unknown'
                        ];
                    }
                }
                
                if (!empty($items)) {
                    return $items;
                }
            }
        }
        
        $lines = explode("\n", $response);
        $items = [];
        
        foreach ($lines as $line) {
            if (preg_match('/[•\-\*]\s*(.*?)(?:\s*:\s*(.*?))?$/i', $line, $matches)) {
                $fullItem = trim($matches[1]);
                $description = isset($matches[2]) ? trim($matches[2]) : '';
                
                $category = '';
                $color = '';
                $name = $fullItem;
                
                $categories = ['t-shirt', 'chemise', 'pantalon', 'jean', 'robe', 'veste', 'manteau',
                              'pull', 'sweatshirt', 'chaussures', 'baskets', 'sneakers', 'accessoire'];
                
                foreach ($categories as $cat) {
                    if (stripos($fullItem, $cat) !== false) {
                        $category = $cat;
                        break;
                    }
                }
                
                $colors = ['noir', 'blanc', 'rouge', 'bleu', 'vert', 'jaune', 'marron', 'gris', 'violet', 'rose', 'orange'];
                
                foreach ($colors as $col) {
                    if (stripos($fullItem, $col) !== false) {
                        $color = $col;
                        break;
                    }
                }
                
                $items[] = [
                    'name' => $name,
                    'category' => $category,
                    'color' => $color,
                    'brand' => 'Unknown'
                ];
            }
        }
        
        return !empty($items) ? $items : $this->fallbackParsing($response);
    }

    private function fallbackParsing(string $response): array
    {
        $items = [];
        $clothingTypes = [
            't-shirt' => 'tops', 
            'chemise' => 'tops', 
            'pantalon' => 'bottoms', 
            'jean' => 'bottoms', 
            'robe' => 'dresses', 
            'veste' => 'outerwear', 
            'manteau' => 'outerwear', 
            'pull' => 'tops', 
            'sweatshirt' => 'tops', 
            'chaussures' => 'shoes', 
            'baskets' => 'shoes', 
            'sneakers' => 'shoes'
        ];
        
        foreach ($clothingTypes as $type => $category) {
            if (stripos($response, $type) !== false) {
                $items[] = [
                    'name' => ucfirst($type),
                    'category' => $category,
                    'color' => 'Unknown',
                    'brand' => 'Unknown'
                ];
            }
        }
        
        return $items;
    }
    
    public function getRecommendationsForUser(User $user, array $availableItems, string $occasion = null, string $season = null): array
    {
        $userPreferences = $this->getUserPreferences($user);
        
        if ($occasion || $season) {
            $availableItems = array_filter($availableItems, function($item) use ($occasion, $season) {
                $matchOccasion = true;
                
                if ($occasion && isset($item['style'])) {
                    $style = strtolower($item['style']);
                    $occasion = strtolower($occasion);
                    
                    $occasionMatches = [
                        'casual' => ['casual', 'décontract'],
                        'formel' => ['formel', 'élégant'],
                        'soirée' => ['soirée', 'fête']
                    ];
                    
                    if (isset($occasionMatches[$occasion])) {
                        $matchOccasion = array_reduce($occasionMatches[$occasion], 
                            fn($match, $term) => $match || strpos($style, $term) !== false, 
                            false
                        );
                    }
                }
                
                return $matchOccasion;
            });
        }
        
        return $this->getRecommendations($userPreferences, $availableItems);
    }
    
    private function getUserPreferences(User $user): array
    {
        $wardrobeItems = $user->getWardrobeItems();
        
        $userWardrobe = [];
        $favoriteItems = [];
        
        foreach ($wardrobeItems as $wardrobeItem) {
            $item = $wardrobeItem->getClothingItem();
            
            $itemData = [
                'id' => $item->getId(),
                'name' => $item->getName(),
                'category' => $item->getCategory()->getName(),
                'color' => $item->getColor(),
                'style' => $item->getStyle(),
                'usage_count' => $wardrobeItem->getUsageCount()
            ];
            
            $userWardrobe[] = $itemData;
            
            if ($wardrobeItem->isFavorite()) {
                $favoriteItems[] = $itemData;
            }
        }
        
        $userOutfits = array_map(function($outfit) {
            return [
                'title' => $outfit->getTitle(),
                'items' => array_map(fn($item) => [
                    'id' => $item->getId(),
                    'name' => $item->getName(),
                    'category' => $item->getCategory()->getName(),
                    'color' => $item->getColor(),
                    'style' => $item->getStyle()
                ], $outfit->getClothingItems()->toArray()),
                'created_at' => $outfit->getCreatedAt()->format('Y-m-d')
            ];
        }, $user->getOutfits()->toArray());
        
        return [
            'wardrobe' => $userWardrobe,
            'favorite_items' => $favoriteItems,
            'outfit_history' => $userOutfits
        ];
    }
    
    public function getRecommendations(array $userPreferences, array $availableItems): array
    {
        $prompt = $this->buildPrompt($userPreferences, $availableItems);
        
        $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01'
            ],
            'json' => [
                'model' => 'claude-3-7-sonnet-20250219',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.7,
                'max_tokens' => 2000
            ],
            'timeout' => 30
        ]);
        
        $responseContent = $response->getContent();
        return $this->processResponse($responseContent);
    }
    
    private function buildPrompt(array $userPreferences, array $availableItems): string
    {
        $itemsJson = json_encode($availableItems, JSON_PRETTY_PRINT);
        $preferencesJson = json_encode($userPreferences, JSON_PRETTY_PRINT);
        
        $filters = $userPreferences['filters'] ?? [];
        $occasionFilter = $filters['occasion'] ?? null;
        $seasonFilter = $filters['season'] ?? null;
        $colorFilter = $filters['color'] ?? null;
        
        $filterInstructions = "";
        $occasionMap = [
            'casual' => "- Les tenues doivent être casual et décontractées, adaptées à un usage quotidien.\n",
            'formal' => "- Les tenues doivent être formelles et élégantes, adaptées à un contexte professionnel ou habillé.\n",
            'party' => "- Les tenues doivent être adaptées aux soirées et événements festifs.\n",
            'sport' => "- Les tenues doivent être sportives et confortables, adaptées à l'activité physique.\n"
        ];
        
        if ($occasionFilter && isset($occasionMap[$occasionFilter])) {
            $filterInstructions .= $occasionMap[$occasionFilter];
        }
        
        if ($seasonFilter) {
            $filterInstructions .= "- Les tenues doivent être adaptées à la saison : $seasonFilter.\n";
        }
        
        if ($colorFilter) {
            $filterInstructions .= "- Les tenues doivent inclure principalement la couleur : $colorFilter.\n";
        }
        
        return <<<PROMPT
Je veux que tu agisses comme un styliste personnel expert qui recommande des tenues basées sur les vêtements disponibles dans la garde-robe d'un utilisateur.

Voici les préférences et historique de tenues de l'utilisateur:
$preferencesJson

Voici les articles disponibles:
$itemsJson

Génère 4 recommandations de tenues complètes en suivant ces règles:
1. Priorise les vêtements de la garde-robe personnelle de l'utilisateur (wardrobe)
2. Prends en compte les préférences de l'utilisateur (favorite_items)
3. Analyse l'historique des tenues pour comprendre le style personnel (outfit_history)
4. Chaque tenue doit contenir au moins un haut et un bas (ou une pièce complète comme une robe)
5. Les couleurs doivent être harmonieuses et correspondre aux préférences de l'utilisateur
6. La tenue doit être adaptée à une occasion spécifique parmi ces 4 catégories précises:
   - Casual: tenues décontractées, quotidiennes, simples, pour la journée
   - Formal: looks formels, élégants, chics, professionnels, adaptés au bureau
   - Party: tenues pour soirées, fêtes, événements, cocktails ou sorties
   - Sport: ensembles sportifs, actifs, athlétiques, pour le fitness, la gym, l'entraînement ou toute activité physique
7. Assure-toi que les recommandations sont variées (pas toutes pour la même occasion)
8. Utilise les mots-clés appropriés dans ta description pour chaque style:
   - Pour Casual: casual, décontracté, quotidien, jour, simple
   - Pour Formal: formel, élégant, chic, professionnel, bureau
   - Pour Party: soirée, fête, événement, cocktail, sortie
   - Pour Sport: sport, actif, athlétique, fitness, gym, entraînement, activité physique, sportif
$filterInstructions

Réponds UNIQUEMENT en format JSON avec exactement cette structure:
{
  "recommendations": [
    {
      "title": "Titre de la tenue",
      "description": "Description détaillée incluant l'occasion et pourquoi ces pièces fonctionnent ensemble",
      "items": [ID1, ID2, ID3...],
    },
    ...
  ]
}

N'inclus aucun texte avant ou après le JSON.
PROMPT;
    }
    
    private function processResponse(string $responseContent): array
    {
        $data = json_decode($responseContent, true);
        
        $jsonContent = $data['content'][0]['text'] ?? 
                       $data['completion'] ?? 
                       $data['message']['content'] ?? null;
        
        if (preg_match('/\{[\s\S]*\}/m', $jsonContent, $matches)) {
            $potentialJson = $matches[0];
            $recommendations = json_decode($potentialJson, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($recommendations['recommendations'])) {
                return $recommendations['recommendations'];
            }
        }
        
        $recommendations = json_decode($jsonContent, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($recommendations['recommendations'])) {
            return $recommendations['recommendations'];
        }
        
        return [];
    }
}