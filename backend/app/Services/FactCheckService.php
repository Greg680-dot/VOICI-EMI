<?php

namespace App\Services;

use App\Contracts\AiProviderInterface;
use App\DTOs\FactCheckDto;
use App\Models\FactCheck;
use App\Repositories\FactCheckRepository;

class FactCheckService
{
    public function __construct(
        protected FactCheckRepository $repository,
        protected AiProviderInterface $aiProvider
    ) {}

    public function submit(FactCheckDto $dto): FactCheck
    {
        return $this->repository->create([
            'user_id' => $dto->userId,
            'content_type' => $dto->contentType,
            'content_value' => $dto->contentValue,
            'ip_address' => $dto->ipAddress,
            'status' => 'submitted'
        ]);
    }

    public function analyzeWithAi(FactCheck $factCheck): FactCheck
    {
        $factCheck->update(['status' => 'analyzing']);

        $aiResult = $this->aiProvider->analyze($factCheck->content_value, $factCheck->content_type);

        $factCheck->update([
            'ai_score' => $aiResult['score'],
            'ai_analysis' => $aiResult['analysis'],
            'status' => 'analyzed'
        ]);

        return $factCheck;
    }

    public function validateAndPublish(FactCheck $factCheck, int $validatorId, string $verdict, string $rating): FactCheck
    {
        $factCheck->update([
            'fact_checker_id' => $validatorId,
            'final_verdict' => $verdict,
            'reliability_rating' => $rating,
            'status' => 'published'
        ]);

        AuditLogService::log(
            action: 'verdict_publication',
            description: "Verdict de fact-check officiel publié pour la soumission #{$factCheck->id} avec l'évaluation : {$rating}.",
            targetUserId: $factCheck->user_id,
            metadata: [
                'fact_check_id' => $factCheck->id,
                'reliability_rating' => $rating,
                'validator_id' => $validatorId
            ]
        );

        return $factCheck;
    }
}
