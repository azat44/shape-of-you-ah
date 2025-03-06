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
        if (empty($this->apiKey)) {
            throw new \Exception('Clé API Claude non configurée');
        }
        
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
            'timeout' => 30, 
        ]);
        
        $responseContent = $response->getContent();
        $result = $this->processResponse($responseContent);
        
        if (empty($result)) {
            throw new \Exception('Aucune recommandation n\'a pu être générée par l\'API');
        }
        
        return $result;
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
      "confidence": 0.85
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
        
        if (!$jsonContent) {
            throw new \Exception('Format de réponse Claude non reconnu');
        }
        
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
        
        throw new \Exception('Impossible de trouver un JSON valide dans la réponse de l\'API');
    }
}