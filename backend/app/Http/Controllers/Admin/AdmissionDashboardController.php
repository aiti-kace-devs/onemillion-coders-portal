<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdmissionsDashboardService;
use Illuminate\Http\Request;

class AdmissionDashboardController extends Controller
{
    protected $service;

    public function __construct(AdmissionsDashboardService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = [
            'summary' => $this->service->getSummaryStats(),
            'byBranch' => $this->service->getAdmissionsByBranch(),
            'byProgramme' => $this->service->getAdmissionsByProgramme(),
            'byCentre' => $this->service->getAdmissionsByCentre(),
            'byDistrict' => $this->service->getAdmissionsByDistrict(),
            'byConstituency' => $this->service->getAdmissionsByConstituency(),
            'mismatch' => $this->service->getLevelMismatchStats(),
        ];

        return view('admin.admission.dashboard', $data);
    }

    public function getCentreDetails($id)
    {
        return response()->json($this->service->getCentreProgrammeDetails((int)$id));
    }

    public function getBranchDetails($id)
    {
        return response()->json($this->service->getBranchProgrammeDetails((int)$id));
    }

    public function getDistrictDetails($id)
    {
        return response()->json($this->service->getDistrictProgrammeDetails((int)$id));
    }

    public function getConstituencyDetails($id)
    {
        return response()->json($this->service->getConstituencyProgrammeDetails((int)$id));
    }

    public function getProgrammeDetails($title)
    {
        return response()->json($this->service->getProgrammeDetailedStats(urldecode($title)));
    }
}
