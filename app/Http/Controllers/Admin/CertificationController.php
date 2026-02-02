<?php

namespace App\Http\Controllers\Admin;

use App\Models\Accreditation;
use App\Http\Controllers\Controller;
use App\Http\Resources\AccreditationCollection;
use App\Http\Resources\AccreditationResource;
use App\Notifications\CertificationSent;
use App\Notifications\CertificationSigned;
use App\Notifications\CertificationPrinted;
use Illuminate\Http\Request;
use Storage;

class CertificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $search = $request->query('search');
        $perPage = 10;

        $query = Accreditation::with(['institution', 'evaluation'])
            ->accredited();

        if ($user->isAssessee()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isProvince()) {
            $query->whereHas('institution', function ($q) use ($user) {
                $q->where('province_id', $user->province_id);
            });
        } elseif (!($user->isSuperAdmin() || $user->isCertificateAdmin())) {
            $query->whereHas('institution', function ($q) use ($user) {
                $q->where('region_id', $user->region_id);
            });
        }
        if ($search !== null && trim($search) !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                ->orWhere('predicate', 'like', "%{$search}%")
                ->orWhereHas('institution', function ($qi) use ($search) {
                    $qi->where('agency_name', 'like', "%{$search}%")
                        ->orWhere('library_name', 'like', "%{$search}%");
                });
            });
        }

        // return $query->paginate($perPage);
        return AccreditationResource::collection(
            $query->paginate($perPage)
        );
    }

    public function show($accreditationId)
    {
        $accreditation = Accreditation::with(['institution', 'evaluation'])->accredited()->findOrFail($accreditationId);

        return new AccreditationResource($accreditation);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        
        $accreditation = Accreditation::accredited()->findOrFail($request->get('accreditation_id'));

        $request->validate([
            'certificate_status' => 'required|in:'.implode(',', Accreditation::certificateStatusList()),
            'certificate_sent_at' => 'required_if:certificate_status,dikirim|date|date_format:Y-m-d',
            'certificate_file' => 'nullable|file|mimes:pdf|max:2048',
            'recommendation_file' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        $data = $request->all();
        
        $accreditation->certificate_status = $request->get('certificate_status');
        $accreditation->certificate_sent_at = $request->get('certificate_sent_at');

        // Simpan file sertifikat
        if ($request->hasFile('certificate_file')) {
            
            $data['certificate_file'] = $request->file('certificate_file')
                                               ->store(
                                                  "certifications/{$accreditation["id"]}",
                                                  'local'
                                               );

            $accreditation->certificate_file = $data['certificate_file'] ?? $accreditation->certification_file;
        }

        // Simpan file rekomendasi akreditasi
        if ($request->hasFile('recommendation_file')) {
            $data['recommendation_file'] = $request->file('recommendation_file')
                                               ->store(
                                                  "recommendations/{$accreditation["id"]}",
                                                  'local'
                                               );

            $accreditation->recommendation_file = $data['recommendation_file'] ?? $accreditation->recommendation_file;
        }

        $accreditation->save();

        if ($accreditation->certificate_status == Accreditation::CERT_STATUS_SENT && $request->has('certificate_sent_at')) {
            $accreditation->user->notify(new CertificationSent($accreditation));
        } elseif ($accreditation->certificate_status == Accreditation::CERT_STATUS_SIGNED) {
            $accreditation->user->notify(new CertificationSigned($accreditation));
        } elseif ($accreditation->certificate_status == Accreditation::CERT_STATUS_PRINTED) {
            $accreditation->user->notify(new CertificationPrinted($accreditation));
        }

        return new AccreditationResource($accreditation);
    }

    public function downloadCertificate($id)
    {
        $certification = Accreditation::findOrFail($id);

        if (!$certification->certificate_file) {
            abort(404);
        }

        if (!Storage::disk('local')->exists($certification->certificate_file)) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $certification->certificate_file
        );
    }

    public function downloadRecommendation($id)
    {
        $certification = Accreditation::findOrFail($id);

        if (!$certification->recommendation_file) {
            abort(404);
        }

        if (!Storage::disk('local')->exists($certification->recommendation_file)) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $certification->recommendation_file
        );
    }

}
