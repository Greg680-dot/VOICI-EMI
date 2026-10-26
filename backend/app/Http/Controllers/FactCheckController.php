<?php

namespace App\Http\Controllers;

use App\Models\FactCheck;
use App\DTOs\FactCheckDto;
use App\Services\FactCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FactCheckController extends Controller
{
    public function __construct(
        protected FactCheckService $service
    ) {}

    public function submit(Request $request)
    {
        Gate::authorize('create', FactCheck::class);

        $request->validate([
            'content_type' => 'required|string|in:text,link,image,video,document',
            'content_value' => 'required|string',
        ]);

        $dto = FactCheckDto::fromRequest($request);
        $factCheck = $this->service->submit($dto);

        return response()->json($factCheck, 201);
    }

    public function analyzeAi(Request $request, FactCheck $factCheck)
    {
        Gate::authorize('runAi', $factCheck);

        $factCheck = $this->service->analyzeWithAi($factCheck);

        return response()->json($factCheck);
    }

    public function validateVerdict(Request $request, FactCheck $factCheck)
    {
        Gate::authorize('validateAi', $factCheck);

        $request->validate([
            'final_verdict' => 'required|string',
            'reliability_rating' => 'required|string|in:vrai,faux,trompeur,non_verifie',
        ]);

        $factCheck = $this->service->validateAndPublish(
            $factCheck,
            $request->user()->id,
            $request->final_verdict,
            $request->reliability_rating
        );

        return response()->json([
            'message' => 'Verdict publié avec succès.',
            'fact_check' => $factCheck
        ]);
    }

    public function history(Request $request)
    {
        $userId = $request->user()->id;
        $history = FactCheck::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($history);
    }
}
