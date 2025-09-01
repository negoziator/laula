<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Aula\AulaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class AulaController extends Controller
{
    private AulaClient $aulaClient;

    public function __construct(AulaClient $aulaClient)
    {
        $this->aulaClient = $aulaClient;
    }

    /**
     * Get Aula configuration status
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'configured' => $this->aulaClient->isConfigured(),
            'message' => $this->aulaClient->isConfigured() 
                ? 'Aula credentials are configured'
                : 'Aula credentials not configured. Please add AULA_USERNAME and AULA_PASSWORD to your .env file.'
        ]);
    }

    /**
     * Get basic data for all children
     */
    public function children(): JsonResponse
    {
        if (!$this->aulaClient->isConfigured()) {
            return response()->json([
                'success' => false,
                'error' => 'Aula credentials not configured'
            ], 400);
        }

        try {
            $children = $this->aulaClient->fetchBasicData();
            return response()->json([
                'success' => true,
                'children' => $children
            ]);
        } catch (Exception $e) {
            Log::error('Aula children fetch error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set active child
     */
    public function setActiveChild(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string'
        ]);

        if (!$this->aulaClient->isConfigured()) {
            return response()->json([
                'success' => false,
                'error' => 'Aula credentials not configured'
            ], 400);
        }

        try {
            $this->aulaClient->setActiveChild($request->name);
            return response()->json([
                'success' => true,
                'message' => "Active child set to: {$request->name}"
            ]);
        } catch (Exception $e) {
            Log::error('Aula set active child error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get daily overview for active child
     */
    public function dailyOverview(): JsonResponse
    {
        if (!$this->aulaClient->isConfigured()) {
            return response()->json([
                'success' => false,
                'error' => 'Aula credentials not configured'
            ], 400);
        }

        try {
            $overview = $this->aulaClient->fetchDailyOverview();
            return response()->json([
                'success' => true,
                'overview' => $overview
            ]);
        } catch (Exception $e) {
            Log::error('Aula daily overview error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get messages
     */
    public function messages(): JsonResponse
    {
        if (!$this->aulaClient->isConfigured()) {
            return response()->json([
                'success' => false,
                'error' => 'Aula credentials not configured'
            ], 400);
        }

        try {
            $messages = $this->aulaClient->fetchMessages();
            return response()->json([
                'success' => true,
                'messages' => $messages
            ]);
        } catch (Exception $e) {
            Log::error('Aula messages fetch error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get calendar events
     */
    public function calendar(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'nullable|integer|min:1|max:90'
        ]);

        if (!$this->aulaClient->isConfigured()) {
            return response()->json([
                'success' => false,
                'error' => 'Aula credentials not configured'
            ], 400);
        }

        try {
            $days = $request->get('days', 14);
            $calendar = $this->aulaClient->fetchCalendar($days);
            return response()->json([
                'success' => true,
                'calendar' => $calendar,
                'days' => $days
            ]);
        } catch (Exception $e) {
            Log::error('Aula calendar fetch error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
