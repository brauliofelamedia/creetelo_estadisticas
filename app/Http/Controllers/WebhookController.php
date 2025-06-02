<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Subscription;

class WebhookController extends Controller
{
    public function updateCancellationDateSubscription(Request $request)
    {
        try {
            // Obtener la fecha y hora actual
            $date_cancelled = now();
            
            $contact = Contact::where('contact_id', $request->id)->first();
            if (!$contact) {
                return response()->json(['error' => 'El contacto no existe'], 404);
            }

            $subscription = Subscription::where('contactId', $request->id)->first();
            if (!$subscription) {
                return redirect()->back()->with('danger', 'La subscripción no existe');
            }

            // Update related subscription status and cancelled date
            if ($subscription) {
                $subscription->status = 'canceled';
                $subscription->cancelled_at = Carbon::parse($date_cancelled)->format('Y-m-d');
                $subscription->save();
            }

            return response()->json(['message' => 'Se ha cancelado la membresía'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
