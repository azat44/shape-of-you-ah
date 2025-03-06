<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class AdminAIService 
{
    private HttpClientInterface $httpClient;
    private ParameterBagInterface $params;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        ParameterBagInterface $params,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->params = $params;
        $this->logger = $logger;
    }

    public function generateAdminInsights(array $data): array
    {
        try {
            $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/complete', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-API-Key' => $this->params->get('CLAUDE_API_KEY'),
                ],
                'json' => [
                    'model' => 'claude-3-opus-20240229',
                    'prompt' => "Analyse les données suivantes et fournis des insights stratégiques pour l'administration : " . 
                                 json_encode($data),
                    'max_tokens_to_sample' => 500
                ]
            ]);

            $content = $response->toArray();
            return [
                'success' => true,
                'insights' => $content['completion']
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur IA Admin Insights : ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function predictTrends(array $historicalData): array
    {
        try {
            $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/complete', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-API-Key' => $this->params->get('CLAUDE_API_KEY'),
                ],
                'json' => [
                    'model' => 'claude-3-opus-20240229',
                    'prompt' => "Analyse ces données historiques et prédis les tendances futures pour l'application : " . 
                                 json_encode($historicalData),
                    'max_tokens_to_sample' => 500
                ]
            ]);

            $content = $response->toArray();
            return [
                'success' => true,
                'predictions' => $content['completion']
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur IA Predict Trends : ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}