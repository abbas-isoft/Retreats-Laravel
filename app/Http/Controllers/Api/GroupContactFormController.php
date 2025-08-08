<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GroupContactForm;
use Illuminate\Support\Facades\Validator;

class GroupContactFormController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'email'          => 'required|email',
            'phone'          => 'required|string|max:20',
            'interested_in'  => 'required|string|max:255',
            'arrival_date'   => 'nullable|date',
            'departure_date' => 'nullable|date|after_or_equal:arrival_date',
            'message'        => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        $form = GroupContactForm::create($validator->validated());

        return response()->json([
            'status'  => true,
            'message' => 'Form submitted successfully',
            'data'    => $form
        ]);
    }
}
