<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use GuzzleHttp\Client as GuzzleClient;

class ChatbotController extends Controller
{
    // Recibir mensajes desde Twilio Webhook
    public function receiveMessage(Request $request)
    {
        $incomingMessage = $request->input('Body'); // El cuerpo del mensaje de WhatsApp
        $sender = $request->input('From'); // El número de teléfono del remitente

        // Obtener respuesta de ChatGPT
        $responseMessage = $this->getChatGPTResponse($incomingMessage);

        // Enviar respuesta de vuelta a WhatsApp
        $this->sendWhatsAppMessage($sender, $responseMessage);

        return response()->json(['status' => 'success']);
    }

    // Función para enviar mensajes de vuelta a WhatsApp
    private function sendWhatsAppMessage($to, $message)
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $twilio = new Client($sid, $token);

        $twilio->messages->create($to, [
            'from' => env('TWILIO_WHATSAPP_FROM'),
            'body' => $message
        ]);
    }

    // Obtener respuesta de ChatGPT
    private function getChatGPTResponse($message)
    {
        $client = new GuzzleClient();
        $response = $client->post('https://api.openai.com/v1/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'text-davinci-003', // Puedes usar el modelo de ChatGPT que prefieras
                'prompt' => $message,
                'max_tokens' => 150,
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['choices'][0]['text'] ?? 'Lo siento, no tengo una respuesta para eso.';
    }
}
