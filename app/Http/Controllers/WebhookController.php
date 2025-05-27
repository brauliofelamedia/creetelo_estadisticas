<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function updateCancellationDateSubscription(Request $request)
    {
        try {
            // Obtener la fecha y hora actual
            $date_cancelled = now();
            
            $contact = Contact::where('contact_id', $request->input('id'))->first();
            
            if (!$contact) {
                return response()->json(['error' => 'El contacto no existe'], 404);
            }

            $contact->subscription->update([
                'status' => 'canceled', 
                'cancelled_at' => $date_cancelled,
            ]);

            return response()->json(['message' => 'Se ha actualizado la fecha de cancelación'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
