<?php

namespace App\Http\Controllers;

use App\Http\Resources\ImageResource;
use App\Models\Office;
use Illuminate\Http\Request;

class OfficeImageController extends Controller
{
    public function store(Office $office) {
        if(!auth()->user()->tokenCan('office.update')) {
            abort(403);
        }
        $this->authorize('update', $office);
        request()->validate([
            'image' => ['file', 'max:5000', 'mimes:jpg,png']
        ]);
        $path = request()->file('image')->storePublicly('/', ['disk' => 'public']);

        $image = $office->images()->create([
            'path' => $path
        ]);

        return ImageResource::make($image);
    }
}
