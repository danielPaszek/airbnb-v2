<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfficeRequest;
use App\Http\Requests\UpdateOfficeRequest;
use App\Http\Resources\OfficeResource;
use App\Models\Office;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OfficeController extends Controller
{
    public function index() {

        $offices = Office::query()
        ->where('approval_status', Office::APPROVAL_APPROVED)
        ->where('hidden', false)
        ->when(request('user_id'), fn($builder) => $builder->whereUserId(request('user_id')))
        ->when(request('visitor_id'), fn(Builder $builder) => $builder->whereRelation('reservations', 'user_id', '=', request('visitor_id')))
        ->when(
            request('lat') && request('lng'),
            fn(Builder $builder) => $builder->nearestTo(request('lat'), request('lng')),
            fn(Builder $builder) => $builder->orderBy('id', 'ASC')
        )
        ->with(['images', 'tags', 'user'])
        ->withCount(['reservations' => fn(Builder $builder) => $builder->where('status', Reservation::STATUS_ACTIVE)])
        ->paginate(20);
        return OfficeResource::collection(
            $offices
        );
    }
    public function show(Office $office) {
        $office
            ->loadCount(['reservations' => fn(Builder $builder) => $builder->where('status', Reservation::STATUS_ACTIVE)])
            ->load(['images', 'tags', 'user']);
        return OfficeResource::make($office);
    }
    public function create(StoreOfficeRequest $request) {

        //uses hasApiToken trait. ??
        // dd(auth()->user()->accessToken);

        $attributes = $request->validated();

        $attributes['user_id'] = auth()->id();
        $attributes['approval_status'] = Office::APPROVAL_PENDING;

        $office = DB::transaction(function() use($attributes) {
            $office = Office::create(
                Arr::except($attributes, ['tags'])
            );
    
            //change intermediate table 
            $office->tags()->attach($attributes['tags']);
            return $office;
        });
        return OfficeResource::make($office->load(['images', 'tags', 'user']));
    }
    public function update(Office $office, UpdateOfficeRequest $request)
    {

        $attributes = $request->validated();

        $attributes['user_id'] = auth()->id();
        $attributes['approval_status'] = Office::APPROVAL_PENDING;
        
         DB::transaction(function() use($attributes, $office) {
            $office->update(
                Arr::except($attributes, ['tags'])
            );
    
            $office->tags()->sync($attributes['tags']);
            return $office;
        });
        return OfficeResource::make($office->load(['images', 'tags', 'user']));
    }
}
