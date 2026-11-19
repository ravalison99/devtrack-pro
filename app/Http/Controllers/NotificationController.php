<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    private const PAR_PAGE = 10;

    /** Liste des notifications de l'utilisateur connecté, toutes ou seulement les non lues. */
    public function index(Request $request)
    {
        $utilisateur = $request->user();
        $seulementNonLues = $request->query('filtre') === 'non_lues';

        $notifications = ($seulementNonLues ? $utilisateur->unreadNotifications() : $utilisateur->notifications())
            ->latest()
            ->paginate(self::PAR_PAGE)
            ->withQueryString();

        return view('dashboard.notifications', [
            'notifications' => $notifications,
            'seulementNonLues' => $seulementNonLues,
            'nombreNonLues' => $utilisateur->unreadNotifications()->count(),
        ]);
    }

    /** Ouvre une notification : la marque comme lue puis mène à la page concernée. */
    public function open(Request $request, DatabaseNotification $notification)
    {
        $this->verifierProprietaire($request, $notification);

        $notification->markAsRead();

        $chemin = $notification->data['url'] ?? null;

        // Seuls les chemins internes à l'application sont suivis (pas de redirection vers un site externe).
        if (is_string($chemin) && str_starts_with($chemin, '/') && ! str_starts_with($chemin, '//')) {
            return redirect($chemin);
        }

        return redirect()->route('notifications.index');
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }

    public function destroy(Request $request, DatabaseNotification $notification)
    {
        $this->verifierProprietaire($request, $notification);

        $notification->delete();

        return back()->with('success', 'Notification supprimée.');
    }

    private function verifierProprietaire(Request $request, DatabaseNotification $notification): void
    {
        $utilisateur = $request->user();

        abort_unless(
            $notification->notifiable_type === get_class($utilisateur) && (int) $notification->notifiable_id === $utilisateur->id,
            403
        );
    }
}
