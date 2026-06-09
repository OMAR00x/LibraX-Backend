<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIChatController extends Controller
{
    /**
     * Proxies the mobile app AI chat request securely to the Google Gemini API.
     * Keeps the API Key safe on the server and bypasses client-side geoblocking.
     */
    public function chat(Request $request)
    {
        $request->validate([
            'query' => 'required|string',
            'bookTitle' => 'nullable|string',
            'author' => 'nullable|string',
        ]);

        $query = $request->input('query');
        $bookTitle = $request->input('bookTitle', 'عام');
        $author = $request->input('author', 'عام');

        $apiKey = env('GEMINI_API_KEY');

        Log::info('==================== [BACKEND AI CHAT REQUEST] ====================');
        Log::info("الرسالة المرسلة (Query): $query");
        Log::info("Book Title: $bookTitle, Author: $author");
        Log::info("GEMINI_API_KEY configured: " . ($apiKey ? 'Yes (Starts with ' . substr($apiKey, 0, 5) . '...)' : 'No'));
        Log::info('===================================================================');

        if (!$apiKey) {
            Log::error("AI Chat failed: GEMINI_API_KEY is not configured in .env file.");
            return response()->json([
                'status' => 'error',
                'message' => 'API Key is not configured on the server. Please add GEMINI_API_KEY to your backend .env file.'
            ], 500);
        }

        // Formulate the appropriate Arabic context prompt
        $prompt = $bookTitle === 'عام'
            ? "أنت مساعد القراءة والتعلم الذكي لـ LibraX AI 🤖. تجيب عن أسئلة المستخدم بخصوص الثقافة العامة ومساعدته في القراءة وتلخيص الكتب وملفات الـ PDF. أجب باحترافية، وودية، وتفصيل باللغة العربية بناءً على سؤال القارئ التالي:\n\n$query"
            : "أنت مساعد القراءة الذكي لـ LibraX AI 🤖. تجيب عن أسئلة المستخدم بخصوص كتاب '$bookTitle' للكاتب '$author'. أجب باحترافية، وودية، وتفصيل باللغة العربية بناءً على سؤال القارئ التالي:\n\n$query";

        // Try primary model (gemini-3.5-flash is the active stable model)
        $primaryModel = 'gemini-3.5-flash';
        $success = $this->callGeminiApi($primaryModel, $prompt, $apiKey, $reply);

        if ($success && !empty($reply)) {
            return response()->json([
                'status' => 'success',
                'reply' => $reply
            ]);
        }

        // Fallback model (gemini-2.5-flash) for extra redundancy
        Log::warning("Gemini primary model ({$primaryModel}) failed or was rate-limited. Trying fallback model...");
        $fallbackModel = 'gemini-2.5-flash';
        $success = $this->callGeminiApi($fallbackModel, $prompt, $apiKey, $reply);

        if ($success && !empty($reply)) {
            return response()->json([
                'status' => 'success',
                'reply' => $reply
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'تعذر الاتصال بخدمة الذكاء الاصطناعي حالياً. يرجى المحاولة لاحقاً.'
        ], 502);
    }

    /**
     * Executes the HTTP POST request to Google Gemini API.
     */
    private function callGeminiApi(string $model, string $prompt, string $apiKey, ?string &$reply): bool
    {
        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
            
            $requestHeaders = [
                'Content-Type' => 'application/json'
            ];
            
            $requestBody = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ];

            Log::info("Calling Gemini API for model {$model}...");
            Log::info("Request URL: $url");
            Log::info("Request Headers: " . json_encode($requestHeaders));
            Log::info("Request Body: " . json_encode($requestBody));

            $response = Http::withHeaders($requestHeaders)->timeout(25)->post($url, $requestBody);

            Log::info("Gemini API Response for model {$model}:");
            Log::info("Status Code: " . $response->status());
            Log::info("Response Body: " . $response->body());

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                if (!empty($text)) {
                    $reply = trim($text);
                    return true;
                }
            }

            Log::error("Gemini API call returned failure status code for model {$model}", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

        } catch (\Exception $e) {
            Log::error("Exception caught while calling Gemini API for model {$model}", [
                'message' => $e->getMessage()
            ]);
        }

        return false;
    }
}
