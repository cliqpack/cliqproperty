<?php

namespace Modules\Settings\Http\Controllers;

use App\Models\User;
use Dotenv\Exception\ValidationException;
use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException as ValidationValidationException;

class MultipleLanguageController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('settings::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {

            // Validate the request data
            $request->validate([
                'id' => 'required|exists:users,id',
                'language_code' => 'required|string', // You can add more validation rules as needed
            ]);

            // Get the user by ID
            $user = User::find($request->id);

            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }

            // Update the user's language_code
            $user->language_code = $request->language_code;
            $user->save();

            return response()->json([
                'message' => 'Language code updated successfully',
            ], 200);
        } catch (ValidationValidationException $e) {
            // Handle validation errors
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            // Handle other exceptions
            return response()->json([
                'message' => 'Oops! An Exception',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {
            // Find the user by ID
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }

            // Return the user's language code
            return response()->json([
                'message' => 'User language code retrieved successfully',
                'data' => $user->language_code,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Oops! An Exception',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('settings::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
