<?php

namespace Kaely\PosCustomer\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Respuesta de éxito
     */
    protected function successResponse(
        $data = null, 
        string $message = 'Operación exitosa', 
        int $status = 200
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    /**
     * Respuesta de error
     */
    protected function errorResponse(
        string $message = 'Error en la operación', 
        int $status = 400,
        array $errors = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Respuesta de validación
     */
    protected function validationErrorResponse($errors): JsonResponse
    {
        return $this->errorResponse(
            'Los datos proporcionados no son válidos',
            422,
            $errors
        );
    }

    /**
     * Respuesta de no encontrado
     */
    protected function notFoundResponse(string $message = 'Recurso no encontrado'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Respuesta de no autorizado
     */
    protected function unauthorizedResponse(string $message = 'No autorizado'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Respuesta de prohibido
     */
    protected function forbiddenResponse(string $message = 'Acceso prohibido'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Respuesta paginada
     */
    protected function paginatedResponse(
        ResourceCollection $collection,
        LengthAwarePaginator $paginator
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $collection,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * Respuesta de colección simple
     */
    protected function collectionResponse(ResourceCollection $collection): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $collection,
        ]);
    }

    /**
     * Respuesta de recurso simple
     */
    protected function resourceResponse(JsonResource $resource): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $resource,
        ]);
    }
} 