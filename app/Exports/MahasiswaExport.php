<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelWriter;

class MahasiswaExport
{
    public static function downloadTemplate()
    {
        return response()->streamDownload(function () {
            // 1. Inisialisasi Excel Writer langsung ke output stream browser
            $writer = SimpleExcelWriter::create('php://output', 'xlsx');

            // 2. Ambil 2 sampel data dengan Join ke tbprogram agar mendapatkan nama teks (Pagi/Malam)
            $samples = DB::table('tbmahasiswa')
                ->leftJoin('tbprogram', 'tbmahasiswa.program', '=', 'tbprogram.kodeProgram')
                ->select(
                    'tbmahasiswa.npm', 
                    'tbmahasiswa.noRegistrasi as no_reg', 
                    'tbmahasiswa.nama', 
                    'tbmahasiswa.jenisKelamin as jk',
                    'tbmahasiswa.kodeProdi as prodi', 
                    'tbprogram.namaProgram as program_text', // Mengambil teks Nama Program (Pagi/Malam)
                    'tbmahasiswa.kelas', 
                    'tbmahasiswa.tahunAkademik as ta',
                    'tbmahasiswa.jalur', 
                    'tbmahasiswa.agama', 
                    'tbmahasiswa.kip', 
                    'tbmahasiswa.hp', 
                    'tbmahasiswa.email', 
                    'tbmahasiswa.keterangan'
                )
                ->limit(2)
                ->get();

            // 3. Cek Apakah ada data di database
            if ($samples->isEmpty()) {
                // Jika database kosong, paksa isi dengan sampel dummy berformat teks langsung
                $writer->addRows([
                    [
                        'NPM'                      => '222340001', 
                        'No Registrasi'            => 'REG2026001', 
                        'Nama Lengkap'             => 'Muhammad Abu', 
                        'Jenis Kelamin (L/P)'      => 'L', 
                        'Prodi'                    => '62401', 
                        'Program'                  => 'Pagi', // Format teks langsung
                        'Kelas'                    => '1AA', 
                        'Tahun Akademik'           => '20251', 
                        'Jalur Masuk'              => 'J01', 
                        'Agama'                    => '1', 
                        'Penerima KIP (Ya/Tidak)'  => 'Tidak', 
                        'No HP'                    => '08123456789', 
                        'Email'                    => 'abu@student.polsri.ac.id', 
                        'Keterangan'               => 'Mahasiswa Baru'
                    ],
                    [
                        'NPM'                      => '222340002', 
                        'No Registrasi'            => 'REG2026002', 
                        'Nama Lengkap'             => 'Siti Aminah', 
                        'Jenis Kelamin (L/P)'      => 'P', 
                        'Prodi'                    => '62401', 
                        'Program'                  => 'Pagi', // Format teks langsung
                        'Kelas'                    => '1AB', 
                        'Tahun Akademik'           => '2025/2026', 
                        'Jalur Masuk'              => 'J02', 
                        'Agama'                    => '1', 
                        'Penerima KIP (Ya/Tidak)'  => 'Ya', 
                        'No HP'                    => '08987654321', 
                        'Email'                    => 'siti@student.polsri.ac.id', 
                        'Keterangan'               => 'Mahasiswa Baru'
                    ]
                ]);
            } else {
                // Jika database ada isinya, buat baris berdasarkan data riil dari tbmahasiswa
                foreach ($samples as $mhs) {
                    $writer->addRow([
                        'NPM'                      => $mhs->npm,
                        'No Registrasi'            => $mhs->no_reg,
                        'Nama Lengkap'             => $mhs->nama,
                        'Jenis Kelamin (L/P)'      => $mhs->jk,
                        'Prodi'                    => $mhs->prodi,
                        'Program'                  => $mhs->program_text ?? 'Pagi', // Menggunakan hasil join teks
                        'Kelas'                    => $mhs->kelas,
                        'Tahun Akademik'           => $mhs->ta,
                        'Jalur Masuk'              => $mhs->jalur,
                        'Agama'                    => $mhs->agama,
                        'Penerima KIP (Ya/Tidak)'  => $mhs->kip,
                        'No HP'                    => $mhs->hp,
                        'Email'                    => $mhs->email,
                        'Keterangan'               => $mhs->keterangan,
                    ]);
                }
            }

            // 4. Tutup Aliran Data Excel
            $writer->close();
        }, 'template_mahasiswa_baru.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}