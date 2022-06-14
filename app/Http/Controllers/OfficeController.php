<?php

namespace App\Http\Controllers;

use App\Http\Resources\OfficeResource;
use App\Models\Office;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
    public function create() {

        //uses hasApiToken trait. ??
        if(! auth()->user()->tokenCan('office.create')) {
            abort(403);
        }

        $attributes = validator(request()->all(),
        [
            'title' => ['required', 'string'],
            'description' => ['required', 'string'],
            'address_line1' => ['required', 'string'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'hidden' => ['bool'],
            'price_per_day' => ['required', 'integer', 'min:100'],
            'monthly_discount' => ['integer', 'min:0', 'max:99'],

            'tags' => ['array'],
            //query for each tag. Change later!
            'tags.*' => ['integer', Rule::exists('tags', 'id')],
        ])->validate();

        $attributes['user_id'] = auth()->id();
        $attributes['approval_status'] = Office::APPROVAL_PENDING;

        $office = Office::create(
            Arr::except($attributes, ['tags'])
        );

        //not sure why that
        $office->tags()->sync($attributes['tags']);

        return OfficeResource::make($office);
    }
}
