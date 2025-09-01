<?php

namespace App\Services\Aula;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

class AulaClient
{
    private ?string $username;
    private ?string $password;
    private ?string $sessionCookie = null;
    private string $apiUrl = 'https://www.aula.dk/api/v20';
    private ?array $profiles = null;
    private ?string $activeChild = null;
    private array $childIds = [];

    public function __construct(?string $username = null, ?string $password = null)
    {
        $this->username = $username ?? config('services.aula.username');
        $this->password = $password ?? config('services.aula.password');
    }

    public function isConfigured(): bool
    {
        return !empty($this->username) && !empty($this->password);
    }

    public function login(): bool
    {
        if (!$this->isConfigured()) {
            throw new Exception('Aula credentials not configured');
        }

        try {
            // Check if we have a cached session
            $cacheKey = "aula_session_{$this->username}";
            $cachedSession = Cache::get($cacheKey);
            
            if ($cachedSession) {
                $this->sessionCookie = $cachedSession['cookie'];
                $this->profiles = $cachedSession['profiles'];
                $this->childIds = $cachedSession['child_ids'];
                
                // Verify session is still valid
                if ($this->verifySession()) {
                    return true;
                }
            }

            // Perform fresh login
            $response = Http::asForm()->post('https://login.aula.dk/auth/login.php', [
                'type' => 'unilogin'
            ]);

            // Extract form action and perform authentication
            // This is a simplified version - the actual Aula login is more complex
            $loginData = [
                'username' => $this->username,
                'password' => $this->password,
                'selected-aktoer' => 'KONTAKT',
            ];

            $authResponse = Http::asForm()->post('https://login.aula.dk/auth/authenticate', $loginData);

            if ($authResponse->successful()) {
                $this->sessionCookie = $authResponse->cookies()->toArray()[0]['Value'] ?? null;
                
                // Fetch profiles
                $this->fetchProfiles();
                
                // Cache session for 1 hour
                Cache::put($cacheKey, [
                    'cookie' => $this->sessionCookie,
                    'profiles' => $this->profiles,
                    'child_ids' => $this->childIds
                ], 3600);

                return true;
            }

            throw new Exception('Authentication failed');

        } catch (Exception $e) {
            Log::error('Aula login failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function verifySession(): bool
    {
        try {
            $response = Http::withCookies(['session' => $this->sessionCookie], 'aula.dk')
                ->get($this->apiUrl . '?method=profiles.getProfilesByLogin');
            
            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    private function fetchProfiles(): void
    {
        $response = Http::withCookies(['session' => $this->sessionCookie], 'aula.dk')
            ->get($this->apiUrl . '?method=profiles.getProfilesByLogin');

        if ($response->successful()) {
            $data = $response->json();
            $this->profiles = $data['data']['profiles'] ?? [];
            
            // Extract child IDs
            foreach ($this->profiles as $profile) {
                foreach ($profile['children'] ?? [] as $child) {
                    $firstName = explode(' ', $child['name'])[0];
                    $this->childIds[$firstName] = $child['id'];
                }
            }
        }
    }

    public function setActiveChild(string $name): void
    {
        if (!isset($this->childIds[$name])) {
            throw new Exception("Child '{$name}' not found");
        }
        
        $this->activeChild = $name;
    }

    public function fetchBasicData(): array
    {
        $this->ensureLoggedIn();
        
        $childrenData = [];
        
        foreach ($this->profiles as $profile) {
            foreach ($profile['children'] ?? [] as $child) {
                $childrenData[] = [
                    'name' => $child['name'],
                    'institution' => $child['institutionProfile']['institutionName'] ?? 'Unknown'
                ];
            }
        }

        return $childrenData;
    }

    public function fetchDailyOverview(): array
    {
        $this->ensureLoggedIn();
        $this->ensureActiveChild();

        $childId = $this->childIds[$this->activeChild];
        
        $response = Http::withCookies(['session' => $this->sessionCookie], 'aula.dk')
            ->get($this->apiUrl . "?method=presence.getDailyOverview&childIds[]={$childId}");

        if ($response->successful()) {
            $data = $response->json();
            return $data['data'][0] ?? [];
        }

        return [];
    }

    public function fetchMessages(): array
    {
        $this->ensureLoggedIn();

        $response = Http::withCookies(['session' => $this->sessionCookie], 'aula.dk')
            ->get($this->apiUrl . '?method=messaging.getThreads&sortOn=date&orderDirection=desc&page=0');

        if (!$response->successful()) {
            return [];
        }

        $data = $response->json();
        $messages = [];

        foreach ($data['data']['threads'] ?? [] as $thread) {
            $threadResponse = Http::withCookies(['session' => $this->sessionCookie], 'aula.dk')
                ->get($this->apiUrl . "?method=messaging.getMessagesForThread&threadId={$thread['id']}&page=0");

            if ($threadResponse->successful()) {
                $threadData = $threadResponse->json();
                
                $messages[] = [
                    'subject' => $thread['subject'],
                    'messages' => collect($threadData['data']['messages'] ?? [])
                        ->where('messageType', 'Message')
                        ->map(function ($msg) {
                            return [
                                'text' => $msg['text']['html'] ?? $msg['text'] ?? 'No content',
                                'sender' => $msg['sender']['fullName'] ?? 'Unknown sender',
                                'date' => Carbon::parse($msg['sendDateTime'])->format('Y-m-d H:i')
                            ];
                        })
                        ->values()
                        ->toArray()
                ];
            }
        }

        return $messages;
    }

    public function fetchCalendar(int $days = 14): array
    {
        $this->ensureLoggedIn();
        $this->ensureActiveChild();

        $childId = $this->childIds[$this->activeChild];
        $start = Carbon::now()->format('Y-m-d 00:00:00.0000+00:00');
        $end = Carbon::now()->addDays($days)->format('Y-m-d 00:00:00.0000+00:00');

        $response = Http::withCookies(['session' => $this->sessionCookie], 'aula.dk')
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->apiUrl . '?method=calendar.getEventsByProfileIdsAndResourceIds', [
                'instProfileIds' => [$childId],
                'resourceIds' => [],
                'start' => $start,
                'end' => $end
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $events = collect($data['data'] ?? [])
                ->filter(function ($event) use ($childId) {
                    return in_array($childId, $event['belongsToProfiles'] ?? []);
                })
                ->map(function ($event) {
                    $startTime = Carbon::parse($event['startDateTime']);
                    $endTime = Carbon::parse($event['endDateTime']);
                    
                    return [
                        'title' => $event['title'],
                        'start' => $startTime->format('Y-m-d H:i'),
                        'end' => $endTime->format('Y-m-d H:i'),
                        'date' => $startTime->format('Y-m-d'),
                        'formatted_time' => $startTime->format('H:i') . ' - ' . $endTime->format('H:i')
                    ];
                })
                ->groupBy('date')
                ->toArray();

            return $events;
        }

        return [];
    }

    private function ensureLoggedIn(): void
    {
        if (!$this->sessionCookie || !$this->verifySession()) {
            $this->login();
        }
    }

    private function ensureActiveChild(): void
    {
        if (!$this->activeChild) {
            throw new Exception('No active child set. Use setActiveChild() first.');
        }
    }
}
