<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\MembershipResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MembershipController extends Controller
{
    /**
     * Permite listar por filtros las membresias.
     */
    public function index(Request $request): JsonResponse | AnonymousResourceCollection
    {
        try {
            if (!$request->hasAny(['user', 'pay', 'start_date'])) {
                $memberships = Membership::with('user')->paginate(10);
                return MembershipResource::collection($memberships)->additional([
                    'message' => 'Membresias listadas',
                    'success' => true
                ]);
            }

            $query = Membership::query();

            if ($request->filled('user')) {
                $query->where('user_id', $request->string('user')->trim());
            }

            if ($request->filled('pay') && in_array(ucfirst($request->string('user')), ['Debe', 'Pagado'])) {
                $query->where('pay', ucfirst($request->string('pay')->trim()));
            }

            if ($request->filled('start_date')) {
                $query->where('start_date', $request->string('start_date')->trim());
            }

            return MembershipResource::collection($query->with('user')->paginate(10))->additional([
                'message' => 'Membresias listados',
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'Ocurrió un error al filtrar las membresias',
                'success' => false
            ]);
        }
    }

    /**
     * Crea una nueva membresia.
     */
    public function store(Request $request)
    {
        try {
            $rules = [
                'user' => 'exists:App\Models\User,id',
                'start_date' => ['required', Rule::date()->format('Y-m-d')],
                'end_date' => ['required', Rule::date()->format('Y-m-d'), 'after:start_date'],
                'pay' => ['required', Rule::in(['Debe', 'Pagado'])],
                'balance' => ['integer'],
            ];

            $messages = [
                'user.exists' => 'El usuario especificado, no existe.',
                'start_date.required' => 'La fecha de inicio de la membresia es requerida',
                'start_date.date_format' => 'La fecha de inicio de la membresia no cumple con el formato (AAAA-MM-DD)',
                'end_date.required' => 'La fecha de fin de la membresia es requerida',
                'end_date.date_format' => 'La fecha de fin de la membresia no cumple con el formato (AAAA-MM-DD)',
                'end_date.after' => 'La fecha de fin de la membresia debe ser mayor a la de inicio',
                'pay.required' => 'El estado del pago es requerido',
                'pay.in' => 'El estado del pago solo puede ser (Pagado, Debe)',
                'balance.integer' => 'El saldo deben ser solo números'
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                $formatMessages = collect($validator->errors()->messages())->map(function($error) {
                    return $error[0];
                });
                return response()->json([
                    'data' => [],
                    'message' => 'No fue posible crear el usuario',
                    'success' => false,
                    'errors' => $formatMessages
                ]);
            }

            $validatedData = $validator->validated();

            $membership = new Membership;

            $membership->user_id = $validatedData['user'];
            $membership->start_date = $validatedData['start_date'];
            $membership->end_date = $validatedData['end_date'];
            $membership->pay = $validatedData['pay'];
            $membership->state = 'ACTIVE';

            if (isset($validatedData['balance'])) {
                $membership->balance = $validatedData['balance'];
            }

            $membership->save();

            return response()->json([
                'data' => $membership,
                'message' => 'Membresia creada con éxito.',
                'success' => true
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'data' => [],
                'message' => 'Ocurrió un error al crear la membresia',
                'success' => $th->getFile()
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
