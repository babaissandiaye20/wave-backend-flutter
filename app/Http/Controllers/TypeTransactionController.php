<?php

namespace App\Http\Controllers;

use App\Services\TypeTransactionService;
use Illuminate\Http\Request;

class TypeTransactionController extends Controller
{
    protected $typeTransactionService;

    public function __construct(TypeTransactionService $typeTransactionService)
    {
        $this->typeTransactionService = $typeTransactionService;
    }

    public function index()
    {
        return response()->json($this->typeTransactionService->getAllTypes());
    }

    public function show($id)
    {
        $typeTransaction = $this->typeTransactionService->getTypeById($id);
        if (!$typeTransaction) {
            return response()->json(['message' => 'Type de transaction non trouvé'], 404);
        }
        return response()->json($typeTransaction);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nom' => 'required|string|max:255',
        ]);

        $typeTransaction = $this->typeTransactionService->createType($validatedData);
        return response()->json($typeTransaction, 201);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'nom' => 'required|string|max:255',
        ]);

        $typeTransaction = $this->typeTransactionService->updateType($id, $validatedData);
        if (!$typeTransaction) {
            return response()->json(['message' => 'Type de transaction non trouvé'], 404);
        }

        return response()->json($typeTransaction);
    }

    public function destroy($id)
    {
        $deleted = $this->typeTransactionService->deleteType($id);
        if (!$deleted) {
            return response()->json(['message' => 'Type de transaction non trouvé'], 404);
        }
        return response()->json(['message' => 'Type de transaction supprimé']);
    }
}
