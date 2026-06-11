<?php

namespace App\Http\Controllers;

use App\Models\TransportVehicle;
use App\Models\TransportRoute;
use App\Models\TransportAssignment;
use App\Models\Student;
use Illuminate\Http\Request;

class TransportController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('transport.page_title'));
    }

    public function index()
    {
        $institutionId = $this->getInstitutionId();
        $vehicles = TransportVehicle::when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
            ->with('routes.assignments.student')
            ->get();
        $routes = TransportRoute::when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
            ->with(['vehicle', 'assignments.student'])
            ->get();
        $students = Student::when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        return view('transport.index', compact('vehicles', 'routes', 'students'));
    }

    public function storeVehicle(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $data = $request->validate([
            'plate_number' => 'required|string|max:30',
            'capacity' => 'required|integer|min:1',
            'driver_name' => 'nullable|string|max:100',
            'driver_phone' => 'nullable|string|max:30',
        ]);

        TransportVehicle::create($data + ['institution_id' => $institutionId]);

        return back()->with('success', __('transport.vehicle_saved'))->with('transport_tab', 'tab-vehicles');
    }

    public function storeRoute(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'transport_vehicle_id' => 'nullable|exists:transport_vehicles,id',
            'departure_time' => 'nullable',
            'zones' => 'nullable|string|max:255',
        ]);

        TransportRoute::create($data + ['institution_id' => $institutionId]);

        return back()->with('success', __('transport.route_saved'))->with('transport_tab', 'tab-routes');
    }

    public function assignStudent(Request $request)
    {
        $data = $request->validate([
            'transport_route_id' => 'required|exists:transport_routes,id',
            'student_id' => 'required|exists:students,id',
            'pickup_point' => 'nullable|string|max:150',
        ]);

        TransportAssignment::updateOrCreate(
            ['transport_route_id' => $data['transport_route_id'], 'student_id' => $data['student_id']],
            ['pickup_point' => $data['pickup_point'] ?? null, 'status' => 'active']
        );

        return back()->with('success', __('transport.assignment_saved'))->with('transport_tab', 'tab-assign');
    }
}
