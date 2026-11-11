<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\StoreEnquiryRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Services\EnquiryService;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function create(Request $request)
    {
        $product = $request->filled('product')
            ? Product::published()->where('slug', $request->string('product')->toString())->first()
            : null;

        return view('public.contact', [
            'branches' => Branch::active()->ordered()->with('openingHours')->get(),
            'product' => $product,
            'user' => $request->user()?->isCustomer() ? $request->user() : null,
        ]);
    }

    public function store(StoreEnquiryRequest $request, EnquiryService $enquiries)
    {
        $productId = $request->filled('product')
            ? Product::published()->where('slug', $request->string('product')->toString())->value('id')
            : null;

        $result = $enquiries->submit(
            $request->safe()->only(['name', 'email', 'phone', 'subject', 'message']),
            $request->user(),
            $productId,
        );

        return redirect()->route('contact')
            ->with('enquiry_sent', true)
            ->with('enquiry_acknowledged', $result['acknowledged']);
    }
}
