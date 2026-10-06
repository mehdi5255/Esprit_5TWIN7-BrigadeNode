<?php

namespace App\Http\Controllers\Concerns;

use Closure;
use DomainException;
use Illuminate\Http\RedirectResponse;

trait ExecuteDesTransitions
{
    /**
     * Exécute une transition de workflow ; un refus métier revient en arrière avec le message.
     * $redirection peut être une closure, évaluée après l'action (ex. pour une demande tout juste créée).
     */
    protected function executer(callable $action, string $message, string|Closure $redirection): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect($redirection instanceof Closure ? $redirection() : $redirection)->with('success', $message);
    }
}
