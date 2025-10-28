<?php

namespace App\Exceptions\Renderers;

use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ValidationExceptionRenderer
{
    public function __invoke(ValidationException $exception): Response
    {
        // 👇 Only JSON if request is API or expects JSON
        if (request()->is('api/*') || request()->expectsJson()) {
            $firstMessage = collect($exception->errors())->flatten()->first();

            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => $firstMessage,
            ], 422);
        }

        // 👇 Otherwise, let Laravel handle it (redirect + $errors)
        return redirect()
            ->back()
            ->withErrors($exception->errors())
            ->withInput();
    }
}
