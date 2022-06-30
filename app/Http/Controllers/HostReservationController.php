<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetHostReservations;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use Illuminate\Http\Request;

class HostReservationController extends Controller
{
    //query reservations on your offices
    public function index(GetHostReservations $request) {
        $request->validated();
        if(!auth()->user()->tokenCan('reservations.show')) {
            abort(403);
        }
        $reservations = Reservation::query()
            ->whereRelation('office', 'user_id', '=', auth()->id())
            ->when(request('user_id'),
                fn($query) => $query->where('user_id', request('user_id'))
            )->when(request('status'),
                fn($query) => $query->where('status', request('status'))
            )->when(request('from_date') && request('to_date'),
                function($query) {
                    $query->where(function($query) {
                        $query->whereBetween('start_date', [request('from_date'), request('to_date')])
                        ->orWhereBetween('end_date', [request('from_date'), request('to_date')]);
                    });
            })
            ->with(['office', 'office.featuredImage'])
            ->paginate(20);
        
        return ReservationResource::collection(
            $reservations
        );
    }
}
