<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfficeRequest;
use App\Http\Requests\UpdateOfficeRequest;
use App\Http\Resources\OfficeResource;
use App\Models\Office;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\OfficePendingApproval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OfficeController extends Controller
{
    public function index() {

        $offices = Office::query()
        ->when(request('user_id') && auth()->user() && request('user_id') == auth()->id(),
            fn($builder) => $builder,
            fn($builder) => $builder->where('approval_status', Office::APPROVAL_APPROVED)
            ->where('hidden', false)
            )
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
            if (isset($attributes['tags'])) {
                $office->tags()->attach($attributes['tags']);
            }
            return $office;
        });
        Notification::send(User::where('is_admin', true)->get(), new OfficePendingApproval($office));

        return OfficeResource::make($office->load(['images', 'tags', 'user']));
    }
    public function update(Office $office, UpdateOfficeRequest $request)
    {
        //check if office belongs to user
        $this->authorize('update', $office);
        //custom request checks if sanctum token is valid 
        $attributes = $request->validated();
        $attributes['user_id'] = auth()->id();
    
        $office->fill(Arr::except($attributes, ['tags']));
        if($requiresReview = $office->isDirty(['title', 'description', 'address_line1'])) {
            $office->approval_status = Office::APPROVAL_PENDING;
        }
        
         DB::transaction(function() use($attributes, $office) {
            $office->save();
    
            if (isset($attributes['tags'])) {
                $office->tags()->sync($attributes['tags']);
            }
            return $office;
        });
        if($requiresReview) {
            Notification::send(User::where('is_admin', true)->get(), new OfficePendingApproval($office));
        }
        return OfficeResource::make($office->load(['images', 'tags', 'user']));
    }
    public function delete(Office $office) {
        if(!auth()->user()->tokenCan('office.delete')) {
            abort(403);
        }
        $this->authorize('delete', $office);

        if($office->reservations()->where('status', Reservation::STATUS_ACTIVE)->exists()) {
            throw ValidationException::withMessages(['office' => 'Cannot delete reserved office']);
        }
        $office->delete();
    }
}
