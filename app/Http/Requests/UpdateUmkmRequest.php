<?php
namespace App\Http\Requests;

use App\Models\Umkm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUmkmRequest extends FormRequest  // ← nama class harus sama dengan nama file
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_usaha'          => ['sometimes', 'required', 'string', 'max:255'],
            'pemilik_usaha_id'    => ['sometimes', 'required', 'exists:pemilik_usaha,id'],
            'deskripsi'           => ['nullable', 'string', 'max:5000'],
            'sektor'              => ['sometimes', 'required', 'string', Rule::in(array_keys(Umkm::SEKTOR_LABEL))],
            'sub_sektor'          => ['nullable', 'string', 'max:100'],
            'kabupaten'           => ['sometimes', 'required', 'string', 'max:100'],
            'kecamatan'           => ['sometimes', 'required', 'string', 'max:100'],
            'kelurahan'           => ['nullable', 'string', 'max:100'],
            'alamat_usaha'        => ['sometimes', 'required', 'string', 'max:500'],
            'telepon'             => ['nullable', 'string', 'regex:/^[0-9+\-() ]{8,20}$/'],
            'whatsapp'            => ['nullable', 'string', 'regex:/^[0-9+\-() ]{8,20}$/'],
            'email'               => ['nullable', 'email', 'max:255'],
            'website'             => ['nullable', 'url', 'max:255'],
            'instagram'           => ['nullable', 'string', 'max:100'],
            'jumlah_tenaga_kerja' => ['sometimes', 'required', 'integer', 'min:0', 'max:99999'],
            'tahun_berdiri'       => ['nullable', 'integer', 'min:1900', 'max:' . now()->year],
            'foto_usaha'          => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
            'opd_id'              => ['nullable', 'exists:opd,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_usaha.required'  => 'Nama usaha wajib diisi.',
            'sektor.in'            => 'Sektor usaha tidak valid.',
            'foto_usaha.max'       => 'Ukuran foto maksimal 2MB.',
            'foto_usaha.mimes'     => 'Format foto harus jpeg, png, atau webp.',
        ];
    }
}