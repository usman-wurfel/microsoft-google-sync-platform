<?php

namespace App\Http\Controllers;

use App\Services\MicrosoftIntegrationTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class GraphMailController extends Controller
{
    /**
     * Initiate OAuth for Graph Mail Delivery
     */
    public function connect(Request $request)
    {
        // Store response back URL if provided
        if ($request->has('response_back_url')) {
            session(['graph_mail_callback_url' => $request->input('response_back_url')]);
        }
        
        // IMPORTANT: Set flag to identify this flow in the main callback
        session(['microsoft_integration_type' => 'graph_mail']);
        MicrosoftIntegrationTokenService::applyAppConfig('mail');

        return Socialite::driver('microsoft')
            ->scopes([
                'User.Read',
                'Mail.Send',
            ])
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }
    
    /**
     * OAuth callback handler
     */
    public function callback(Request $request)
    {
        try {
            \Log::info('=== Graph Mail Callback Hit ===');
            MicrosoftIntegrationTokenService::applyAppConfig('mail');
            $user = Socialite::driver('microsoft')->user();
            $accessToken = $user->token;
            \Log::info('Token Received:', [
                'token_present' => !empty($accessToken),
                'user_email' => $user->email ?? null,
            ]);
            if (!$accessToken) {
                throw new \Exception('No access token received from Microsoft');
            }
            MicrosoftIntegrationTokenService::store(
                MicrosoftIntegrationTokenService::KEY_GRAPH_MAIL,
                $accessToken,
                $user->expiresIn ?? null,
                $user->refreshToken ?? null,
                $user->email ?? null  // Store the Microsoft 365 user email/UPN
            );
            
            $callbackUrl = session('graph_mail_callback_url');
            
            if ($callbackUrl) {
                session()->forget('graph_mail_callback_url');
                return redirect($callbackUrl)->with('success', 'Graph Mail Delivery connected successfully!');
            }
            
            return redirect()->route('integrations.index')
                ->with('success', 'Graph Mail Delivery connected successfully!');
                
        } catch (\Exception $e) {
            \Log::error('Graph Mail Callback Error: ' . $e->getMessage());
            return redirect()->route('integrations.index')
                ->with('error', 'Failed to connect Graph Mail Delivery: ' . $e->getMessage());
        }
    }
    
    /**
     * Send email via Microsoft Graph API
     */
    public function sendEmail(Request $request)
    {
        try {
            \Log::info('=== Graph Mail Send Request Started ===');
            \Log::info('Request Data:', $request->all());
            
            $validated = $request->validate([
                'recipient' => 'required|email',
                'subject' => 'required|string',
                'body' => 'required|string',
                'from' => 'nullable|email'
            ]);
            
            \Log::info('Validation passed');
            \Log::info('Authenticated User:', ['user_id' => auth()->id(), 'email' => auth()->user()->email ?? 'N/A']);
            
            $accessToken = MicrosoftIntegrationTokenService::getValidAccessToken(MicrosoftIntegrationTokenService::KEY_GRAPH_MAIL);
            if (!$accessToken) {
                \Log::warning('No valid Graph Mail token (expired or not connected)');
                \Log::info('All session data:', session()->all());
                
                return response()->json([
                    'success' => false,
                    'error' => 'Not authenticated. Please connect first.',
                    'debug' => [
                        'user_authenticated' => auth()->check(),
                        'session_id' => session()->getId(),
                        'available_sessions' => array_keys(session()->all())
                    ]
                ], 401);
            }
            
            // Prepare email message
            $message = [
                'message' => [
                    'subject' => $validated['subject'],
                    'body' => [
                        'contentType' => 'HTML',
                        'content' => $validated['body']
                    ],
                    'toRecipients' => [
                        [
                            'emailAddress' => [
                                'address' => $validated['recipient']
                            ]
                        ]
                    ]
                ],
                'saveToSentItems' => 'true'
            ];
            
            \Log::info('Sending to Microsoft Graph API');
            \Log::info('Message:', $message);
            
            // Send email using Microsoft Graph
            $response = Http::withToken($accessToken)
                ->post('https://graph.microsoft.com/v1.0/me/sendMail', $message);
            
            \Log::info('Graph API Response:', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body()
            ]);
            
            if ($response->successful()) {
                \Log::info('=== Email Sent Successfully ===');
                return response()->json([
                    'success' => true,
                    'message' => 'Email sent successfully',
                    'sent_at' => now()->toISOString()
                ]);
            } else {
                \Log::error('Graph API Error:', [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                
                $errorData = $response->json();
                $errorMessage = 'Failed to send email';
                
                // Specific error handling
                if (isset($errorData['error']['code'])) {
                    if ($errorData['error']['code'] === 'MailboxNotEnabledForRESTAPI') {
                        $errorMessage = 'The connected Microsoft account does not have an active Exchange Online mailbox/license. Please connect with an account that has an Outlook inbox.';
                    } else {
                        $errorMessage = 'Microsoft Graph Error: ' . $errorData['error']['message'];
                    }
                }
                
                return response()->json([
                    'success' => false,
                    'error' => $errorMessage,
                    'details' => $errorData
                ], $response->status());
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Error:', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('=== Exception in sendEmail ===');
            \Log::error('Error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }
}
