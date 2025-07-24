<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Muestra una lista de usuarios
     */
    public function index(Request $request): AnonymousResourceCollection | JsonResponse
    {
        try {
            if (!$request->hasAny(['name', 'last_name', 'registration_date', 'hour', 'state'])) {
                $users = User::with('memberships')->paginate(10);
                return UserResource::collection($users);
            }

            $filterData = User::query();
    
            if ($request->filled('name')) {
                $filterData->where('name', 'like', '%'.$request->string('name')->trim().'%');
            }
    
            if ($request->filled('last_name')) {
                $filterData->orWhere('last_name', 'like', '%'.$request->string('last_name')->trim().'%');
            }

            if ($request->filled('state')) {
                $filterData->orWhere('state', $request->string('state')->trim());
            }
    
            if ($request->filled('registration_date')) {
                $filterData->orWhere(function (Builder $query) use ($request) {
                    $query->where('registration_date', $request->string('registration_date')->trim());
                });
            }
    
            if ($request->filled('hour')) {
                $filterData->orWhere(function (Builder $query) use ($request) {
                    $query->where('hour', 'like', '%'.$request->string('hour')->trim().'%');
                });
            }
    
            return UserResource::collection($filterData->with('memberships')->paginate(10));
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'Ocurrió un error al filtrar los usuarios',
                'success' => false
            ]);
        }
    }

    /**
     * Crea un nuevo usuario.
     */
    public function store(Request $request)
    {
        try {
            $rules = [
                'name' => 'required|min:3|max:40',
                'last_name' => 'required|min:3|max:40',
                'registration_date' => ['required', Rule::date()->format('Y-m-d')],
                'hour' => ['required', Rule::date()->format('H:i')]
            ];
    
            $messages = [
                'name.required' => 'El nombre es requerido',
                'last_name.required' => 'El apellido es requerido',
                'registration_date.required' => 'La fecha de registro es requerida',
                'hour.required' => 'La hora es requerida',
                'min' => 'Mínimo 3 carácteres',
                'max' => 'Máximo 40 carácteres',
                'registration_date.required' => 'La fecha de registro es requerida.',
                'registration_date.date_format' => 'La fecha debe tener el formato YYYY-MM-DD.',
                'hour.required' => 'La hora es requerida.',
                'hour.date_format' => 'La hora debe tener el formato HH:MM en horario de 24 horas.',
                'state.required' => 'El estado es obligatorio.',
                'state.in' => 'El estado debe ser "ACTIVE" o "INACTIVE".',
            ];
    
            $validator = Validator::make($request->all(), $rules, $messages);
    
            if ($validator->fails()) {
                $formatMessages = collect($validator->errors()->messages())->map(function ($error) {
                    return $error[0];
                }); 
    
                return response()->json([
                    'data' => [],
                    'message' => 'No fue posible crear el usuario',
                    'success' => false,
                    'errors' => $formatMessages
                ]);
            }
    
            $validated = $validator->validated();
    
            $user = new User;
    
            $user->name = $validated['name'];
            $user->last_name = $validated['last_name'];
            $user->registration_date = $validated['registration_date'];
            $user->hour = $validated['hour'];
            $user->state = 'ACTIVE';
    
            $user->save();

            return response()->json([
                'data' => $user,
                'message' => 'Usuario creado con éxito.',
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'Ocurrió un error al crear el usuario',
                'success' => false
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
