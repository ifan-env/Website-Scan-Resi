<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Prediksi extends Model
{
    use HasFactory;

    protected $fillable = [
        'gambar',
        'nama_asli',
        'sumber',
        'label',
        'nama_kelas',
        'confidence',
        'prob_ada_resi',
        'prob_tidak_ada_resi',
        'waktu_proses_ms',
        'jumlah_fitur',
        'label_dataset',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'prob_ada_resi' => 'float',
            'prob_tidak_ada_resi' => 'float',
            'waktu_proses_ms' => 'integer',
            'jumlah_fitur' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function getGambarUrlAttribute(): ?string
    {
        return $this->gambar
            ? Storage::disk('public')->url($this->gambar)
            : null;
    }

    public function getConfidencePersenAttribute(): float
    {
        return round($this->confidence * 100, 1);
    }

    public function getAdaResiAttribute(): bool
    {
        return $this->label === 'ada_resi';
    }

    /**
     * Bentuk data yang dikirim ke frontend / API.
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'prediksi' => $this->label,
            'nama_kelas' => $this->nama_kelas,
            'kepercayaan' => round($this->confidence, 4),
            'probabilitas' => [
                'ada_resi' => round($this->prob_ada_resi, 4),
                'tidak_ada_resi' => round($this->prob_tidak_ada_resi, 4),
            ],
            'waktu_proses_detik' => round($this->waktu_proses_ms / 1000, 3),
            'jumlah_fitur' => $this->jumlah_fitur,
            'label_dataset' => $this->label_dataset,
            'sumber' => $this->sumber,
            'gambar_url' => $this->gambar_url,
            'dibuat_pada' => $this->created_at?->toIso8601String(),
        ];
    }

    public function scopeFilter($query, array $filter)
    {
        return $query
            ->when($filter['label'] ?? null, fn ($q, $label) => $q->where('label', $label))
            ->when($filter['sumber'] ?? null, fn ($q, $sumber) => $q->where('sumber', $sumber));
    }
}
