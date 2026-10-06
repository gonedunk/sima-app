<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Spatie\SimpleExcel\SimpleExcelReader;

class AjarDosenController extends Controller
{
  /**
   * Tampilan Utama / Alias
   */
  public function index(Request $request)
  {
    return $this->create($request);
  }

  /**
   * Menampilkan halaman form input beban ajar dosen
   */
  public function create(Request $request)
  {
    $user = auth()->user();
    $prodiUser =
      $user->kode_prodi ?? ($user->prodi ?? ($user->id_prodi ?? null));
    $isSuperAdmin = $user->level === "superadmin" || $user->level === "admin";

    $dosen = DB::table("tbdosen")
      ->select("nip", "nama", "singkatan")
      ->orderBy("nama", "asc")
      ->get();

    $setting = DB::table("tbsetting")->first();
    $tahunAkademikAktif = $setting
      ? substr(trim($setting->ta_aktif), 0, 5)
      : date("Y") . "1";
    $semesterSistem = substr($tahunAkademikAktif, 4, 1);

    // Master Kelas
    $queryKelas = DB::table("tbkelas")
      ->join("tbprodi", "tbkelas.kodeProdi", "=", "tbprodi.kodeProdi")
      ->select(
        "tbkelas.namaKelas",
        "tbkelas.kodeProdi",
        "tbprodi.namaProdi",
        "tbkelas.kodeProgram"
      );

    $queryKelas->where(function ($q) use ($semesterSistem) {
      if ($semesterSistem == "1") {
        $q->whereRaw(
          'SUBSTRING(tbkelas.namaKelas, 1, 1) IN ("1", "3", "5", "7")'
        );
      } else {
        $q->whereRaw(
          'SUBSTRING(tbkelas.namaKelas, 1, 1) IN ("2", "4", "6", "8")'
        );
      }
    });

    if (!$isSuperAdmin && !empty($prodiUser)) {
      $queryKelas->where("tbkelas.kodeProdi", $prodiUser);
    }
    $kelas = $queryKelas->orderBy("tbkelas.namaKelas", "asc")->get();

    // Master Mata Kuliah
    $queryMk = DB::table("tbkurikulum")
      ->select(
        "kodeMk",
        "namaMk",
        "singkatan",
        "totalJamPerMinggu",
        "semester",
        "prodi"
      )
      ->where("statusKurikulum", "A")
      ->where(function ($q) use ($semesterSistem) {
        if ($semesterSistem == "1") {
          $q->whereRaw("MOD(semester, 2) <> 0");
        } else {
          $q->whereRaw("MOD(semester, 2) = 0");
        }
      });

    if (!$isSuperAdmin && !empty($prodiUser)) {
      $queryMk->where("tbkurikulum.prodi", $prodiUser);
    }
    $matakuliah = $queryMk
      ->orderBy("semester", "asc")
      ->orderBy("kodeMk", "asc")
      ->get();

    $listHariAjar = DB::table("tbjamajar")
      ->select("hari")
      ->groupBy("hari")
      ->orderByRaw(
        "FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')"
      )
      ->get();

    // Pencarian Global Riwayat
    $search = $request->get("search");
    $detailJadwalRaw = DB::table("tbajardosen")
      ->join("tbdosen", "tbajardosen.nip", "=", "tbdosen.nip")
      ->join("tbkurikulum", "tbajardosen.kodeMk", "=", "tbkurikulum.kodeMk")
      ->join("tbkelas", "tbajardosen.kelas", "=", "tbkelas.namaKelas")
      ->join("tbjamajar", function ($join) {
        $join
          ->on(
            "tbajardosen.jamAjar",
            "=",
            DB::raw("CAST(tbjamajar.id AS CHAR)")
          )
          ->on("tbajardosen.hari", "=", "tbjamajar.hari");
      })
      ->select(
        "tbdosen.nama as namaDosen",
        "tbajardosen.kodeMk",
        "tbkurikulum.namaMk",
        "tbajardosen.kelas",
        "tbajardosen.hari",
        "tbajardosen.tahunAkademik",
        DB::raw(
          'GROUP_CONCAT(DISTINCT CONCAT(tbjamajar.jamNormal, " (Rmd: ", tbjamajar.jamRamadan, ")") ORDER BY CAST(tbjamajar.jamNormal AS UNSIGNED) ASC SEPARATOR ", ") as daftarJam'
        ),
        DB::raw('(
                    SELECT MIN(CAST(j2.jamNormal AS UNSIGNED)) 
                    FROM tbajardosen ad2
                    JOIN tbjamajar j2 ON ad2.jamAjar = CAST(j2.id AS CHAR) AND ad2.hari = j2.hari
                    WHERE ad2.kelas = tbajardosen.kelas 
                      AND ad2.kodeMk = tbajardosen.kodeMk 
                      AND ad2.hari = tbajardosen.hari 
                      AND ad2.tahunAkademik = tbajardosen.tahunAkademik
                ) as jam_terkecil')
      )
      ->where("tbajardosen.tahunAkademik", $tahunAkademikAktif);

    if (!$isSuperAdmin && !empty($prodiUser)) {
      $detailJadwalRaw->where("tbkelas.kodeProdi", $prodiUser);
    }

    if (!empty($search)) {
      $detailJadwalRaw->where(function ($q) use ($search) {
        $q->where("tbdosen.nama", "LIKE", "%{$search}%")
          ->orWhere("tbkurikulum.namaMk", "LIKE", "%{$search}%")
          ->orWhere("tbajardosen.kelas", "LIKE", "%{$search}%")
          ->orWhere("tbajardosen.hari", "LIKE", "%{$search}%")
          ->orWhere("tbajardosen.kodeMk", "LIKE", "%{$search}%");
      });
    }

    $allDetailJadwal = $detailJadwalRaw
      ->groupBy(
        "tbdosen.nama",
        "tbajardosen.kodeMk",
        "tbkurikulum.namaMk",
        "tbajardosen.kelas",
        "tbajardosen.hari",
        "tbajardosen.tahunAkademik"
      )
      ->orderBy("tbdosen.nama", "asc")
      ->orderByRaw(
        "FIELD(tbajardosen.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')"
      )
      ->orderBy("jam_terkecil", "asc")
      ->get();

    $riwayatAjarGrupDetail = $allDetailJadwal->groupBy("namaDosen");
    $riwayatAjarGrup = collect([]);

    return view(
      "admin.ajardosen.index",
      compact(
        "dosen",
        "kelas",
        "matakuliah",
        "tahunAkademikAktif",
        "listHariAjar",
        "riwayatAjarGrup",
        "riwayatAjarGrupDetail",
        "search"
      )
    );
  }

  /**
   * Endpoint AJAX: Mengambil info prodi kelas & daftar mata kuliah
   */
  public function getTerisiMatakuliah(Request $request)
  {
    $namaKelas = $request->kelas;
    $setting = DB::table("tbsetting")->first();
    $tahunAkademik = $setting
      ? substr(trim($setting->ta_aktif), 0, 5)
      : date("Y") . "1";
    $semesterSistem = substr($tahunAkademik, 4, 1);

    $kelasInfo = DB::table("tbkelas")
      ->where("namaKelas", $namaKelas)
      ->first();
    $kodeProdiKelas = $kelasInfo ? $kelasInfo->kodeProdi : "";

    if (empty($kodeProdiKelas)) {
      return response()->json([
        "success" => false,
        "message" => "Kelas tidak ditemukan atau tidak memiliki prodi.",
      ]);
    }

    $masterMk = DB::table("tbkurikulum")
      ->select("kodeMk", "namaMk", "totalJamPerMinggu", "semester")
      ->where("prodi", $kodeProdiKelas)
      ->where("statusKurikulum", "A")
      ->where(function ($q) use ($semesterSistem) {
        if ($semesterSistem == "1") {
          $q->whereRaw("MOD(semester, 2) <> 0");
        } else {
          $q->whereRaw("MOD(semester, 2) = 0");
        }
      })
      ->get();

    $terisiMk = DB::table("tbajardosen")
      ->where("kelas", $namaKelas)
      ->where("tahunAkademik", "LIKE", $tahunAkademik . "%")
      ->where("jamAjar", ">", 0)
      ->groupBy("kodeMk")
      ->pluck(DB::raw("COUNT(id) as total_terisi"), "kodeMk")
      ->toArray();

    $availableMk = [];
    foreach ($masterMk as $mk) {
      $jamTerpakai = isset($terisiMk[$mk->kodeMk])
        ? (int) $terisiMk[$mk->kodeMk]
        : 0;
      $maxJam = (int) $mk->totalJamPerMinggu;

      if ($jamTerpakai < $maxJam) {
        $availableMk[] = [
          "kodeMk" => $mk->kodeMk,
          "namaMk" => $mk->namaMk,
          "sisa" => $maxJam - $jamTerpakai,
          "text" =>
            $mk->kodeMk .
            " - " .
            $mk->namaMk .
            " (Sisa: " .
            ($maxJam - $jamTerpakai) .
            " Jam)",
        ];
      }
    }

    return response()->json([
      "success" => true,
      "kodeProdi" => $kodeProdiKelas,
      "terisiMk" => $terisiMk,
      "availableMk" => $availableMk,
    ]);
  }

  /**
   * Endpoint AJAX: Memproduksi HTML Checkbox Jam
   */
  public function getCheckboxJam(Request $request)
  {
    $namaKelas = $request->kelas;
    $hari = $request->hari;
    $nip = $request->nip;
    $kodeMk = $request->kodeMk;

    $setting = DB::table("tbsetting")->first();
    $tahunAkademik = $setting
      ? substr(trim($setting->ta_aktif), 0, 5)
      : date("Y") . "1";

    $kelasInfo = DB::table("tbkelas")
      ->where("namaKelas", $namaKelas)
      ->first();

    if (!$kelasInfo || !$hari) {
      return response()->json([
        "success" => false,
        "html" =>
          '<span class="text-muted small">Pilih kelas dan hari terlebih dahulu...</span>',
      ]);
    }

    $maxAllowedJam = 0;
    if (!empty($nip) && !empty($kodeMk)) {
      $mkMaster = DB::table("tbkurikulum")
        ->where("kodeMk", $kodeMk)
        ->first();
      $totalJamKurikulum = $mkMaster ? (int) $mkMaster->totalJamPerMinggu : 0;

      $countPlottingTotal = DB::table("tbajardosen")
        ->where("nip", $nip)
        ->where("kelas", $namaKelas)
        ->where("kodeMk", $kodeMk)
        ->where("tahunAkademik", "LIKE", $tahunAkademik . "%")
        ->count();

      $alokasiMaksimalUtuh = max($countPlottingTotal, $totalJamKurikulum);

      $jamTerisiHariLain = DB::table("tbajardosen")
        ->where("nip", $nip)
        ->where("kelas", $namaKelas)
        ->where("kodeMk", $kodeMk)
        ->where("hari", "!=", $hari)
        ->where("hari", "!=", "-")
        ->where("jamAjar", ">", 0)
        ->where("tahunAkademik", "LIKE", $tahunAkademik . "%")
        ->count();

      $maxAllowedJam = $alokasiMaksimalUtuh - $jamTerisiHariLain;
    }

    $listJam = DB::table("tbjamajar")
      ->where("hari", $hari)
      ->where("kodeProgram", $kelasInfo->kodeProgram)
      ->orderByRaw("CAST(jamNormal AS UNSIGNED) ASC")
      ->get();

    if ($listJam->isEmpty()) {
      return response()->json([
        "success" => false,
        "html" =>
          '<span class="text-danger small">Tidak ada master jam mengajar untuk kombinasi hari & program kelas ini.</span>',
      ]);
    }

    $jadwalEksisting = DB::table("tbajardosen")
      ->join("tbdosen", "tbajardosen.nip", "=", "tbdosen.nip")
      ->where("tbajardosen.kelas", $namaKelas)
      ->where("tbajardosen.hari", $hari)
      ->where("tbajardosen.tahunAkademik", "LIKE", $tahunAkademik . "%")
      ->where("tbajardosen.jamAjar", ">", 0)
      ->select("tbajardosen.jamAjar", "tbdosen.nama as nama_dosen")
      ->get()
      ->pluck("nama_dosen", "jamAjar")
      ->toArray();

    $infoLimit =
      $maxAllowedJam > 0
        ? ' <span class="text-danger">(Maksimal pilih: ' .
          $maxAllowedJam .
          " jam)</span>"
        : "";

    $html =
      '<div class="mb-2">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="checkAllJam" style="cursor:pointer;" ' .
      ($maxAllowedJam > 0 ? "disabled" : "") .
      '>
                        <label class="form-check-label fw-bold text-primary small" style="cursor:pointer;" for="checkAllJam">Pilih Semua Jam Tersedia ' .
      $infoLimit .
      '</label>
                    </div>
                 </div>';
    $html .=
      '<div class="row" id="container-checkbox-jam" data-max-check="' .
      $maxAllowedJam .
      '">';

    foreach ($listJam as $j) {
      $isTerisi = array_key_exists((string) $j->id, $jadwalEksisting);
      $namaDosenPemberat = $isTerisi ? $jadwalEksisting[(string) $j->id] : "";
      $htmlId = "jam_" . $j->id;

      $html .=
        '
            <div class="col-12 col-sm-6 col-md-4 mb-2">
                <div class="p-2 border rounded text-start shadow-xs shadow-sm ' .
        ($isTerisi ? "bg-light-danger border-danger-subtle" : "bg-white") .
        '" style="' .
        ($isTerisi ? "background-color: #fff5f5;" : "") .
        '">
                    <div class="form-check mb-0">
                        <input class="form-check-input item-jam-checkbox" type="checkbox" 
                            name="jamAjar[]" 
                            value="' .
        $j->id .
        '" 
                            id="' .
        $htmlId .
        '" 
                            data-terisi="' .
        ($isTerisi ? "1" : "0") .
        '"
                            style="cursor:' .
        ($isTerisi ? "not-allowed" : "pointer") .
        ';"
                            ' .
        ($isTerisi ? "disabled" : "") .
        '>
                        <label class="form-check-label small fw-semibold d-block" style="cursor:' .
        ($isTerisi ? "not-allowed" : "pointer") .
        ';" for="' .
        $htmlId .
        '">
                            Jam Ke- ' .
        $j->jamNormal .
        '
                            ' .
        ($isTerisi
          ? '<span class="d-block text-danger xsmall font-italic fw-normal text-muted mt-1" style="font-size: 11px;"><i class="fas fa-user-lock"></i> Terisi: ' .
            $namaDosenPemberat .
            "</span>"
          : "") .
        '
                        </label>
                    </div>
                </div>
            </div>';
    }
    $html .= "</div>";

    if ($maxAllowedJam > 0) {
      $html .=
        '
            <script>
                docReady(function() {
                    var maxAllowed = ' .
        $maxAllowedJam .
        ';
                    var container = document.getElementById("container-checkbox-jam");
                    if(container) {
                        var checkboxes = container.querySelectorAll(".item-jam-checkbox:not([disabled])");
                        checkboxes.forEach(function(cb) {
                            cb.addEventListener("change", function() {
                                var checkedCount = container.querySelectorAll(".item-jam-checkbox:checked").length;
                                if (checkedCount > maxAllowed) {
                                    this.checked = false;
                                    alert("Anda hanya boleh memilih " + maxAllowed + " jam sesuai beban mengajar mata kuliah ini!");
                                }
                            });
                        });
                    }
                });

                function docReady(fn) {
                    if (document.readyState === "complete" || document.readyState === "interactive") {
                        setTimeout(fn, 1);
                    } else {
                        document.addEventListener("DOMContentLoaded", fn);
                    }
                }
            </script>';
    }

    return response()->json(["success" => true, "html" => $html]);
  }

  /**
   * Memproses penyimpanan data mengajar via Form Web
   */
  public function store(Request $request)
  {
    $request->validate([
      "nip" => "required|string|max:18",
      "kelas" => "required|string|max:5",
      "kodeMk" => "required|string|max:8",
      "hari" => "required|string|max:10",
      "jamAjar" => "required|array|min:1",
      "tahunAkademik" => "required|string|max:5",
    ]);

    $nip = $request->nip;
    $kelas = strtoupper(trim($request->kelas));
    $kodeMk = $request->kodeMk;
    $hari = $request->hari;
    $arrayJam = $request->jamAjar;
    $tahunAkademik = substr(trim($request->tahunAkademik), 0, 5);

    $mkInfo = DB::table("tbkurikulum")
      ->where("kodeMk", $kodeMk)
      ->first();
    if (!$mkInfo) {
      return redirect()
        ->back()
        ->withInput()
        ->withErrors(["error" => "Data Mata Kuliah tidak valid."]);
    }

    $countPlottingTotal = DB::table("tbajardosen")
      ->where("nip", $nip)
      ->where("kelas", $kelas)
      ->where("kodeMk", $kodeMk)
      ->where("tahunAkademik", "LIKE", $tahunAkademik . "%")
      ->count();

    $totalJamKurikulum = $mkInfo ? (int) $mkInfo->totalJamPerMinggu : 0;
    $alokasiMaksimalUtuh = max($countPlottingTotal, $totalJamKurikulum);

    $jamTerisiHariLain = DB::table("tbajardosen")
      ->where("nip", $nip)
      ->where("kelas", $kelas)
      ->where("kodeMk", $kodeMk)
      ->where("hari", "!=", $hari)
      ->where("hari", "!=", "-")
      ->where("jamAjar", ">", 0)
      ->where("tahunAkademik", "LIKE", $tahunAkademik . "%")
      ->count();

    $limitMaksimalHariIni = $alokasiMaksimalUtuh - $jamTerisiHariLain;

    if (count($arrayJam) > $limitMaksimalHariIni) {
      return redirect()
        ->back()
        ->withInput()
        ->withErrors([
          "jamAjar" =>
            "Gagal menyimpan! Jam yang dicentang (" .
            count($arrayJam) .
            " jam) melebihi sisa batas alokasi mengajar hari ini (" .
            $limitMaksimalHariIni .
            " jam dari total beban " .
            $alokasiMaksimalUtuh .
            " jam).",
        ]);
    }

    $barisDefaultTersedia = DB::table("tbajardosen")
      ->where("nip", $nip)
      ->where("kelas", $kelas)
      ->where("kodeMk", $kodeMk)
      ->where("hari", "-")
      ->where("tahunAkademik", "LIKE", $tahunAkademik . "%")
      ->orderBy("id", "asc")
      ->get();

    foreach ($arrayJam as $jamId) {
      $infoJamMaster = DB::table("tbjamajar")
        ->where("id", $jamId)
        ->first();
      $labelJam = $infoJamMaster ? $infoJamMaster->jamNormal : $jamId;

      $bentrokDosen = DB::table("tbajardosen")
        ->where("nip", $nip)
        ->where("hari", $hari)
        ->where("jamAjar", $jamId)
        ->where("tahunAkademik", "LIKE", $tahunAkademik . "%");

      if ($barisDefaultTersedia->count() > 0) {
        $bentrokDosen->whereNotIn(
          "id",
          $barisDefaultTersedia->pluck("id")->toArray()
        );
      }
      $resBentrokDosen = $bentrokDosen->select("kelas", "kodeMk")->first();

      if ($resBentrokDosen) {
        return redirect()
          ->back()
          ->withInput()
          ->withErrors([
            "jamAjar" => "Batal simpan! Dosen bersangkutan sudah memiliki jadwal mengajar pada Hari {$hari}, Jam Ke-{$labelJam} di kelas {$resBentrokDosen->kelas}.",
          ]);
      }

      $bentrokKelas = DB::table("tbajardosen")
        ->join("tbdosen", "tbajardosen.nip", "=", "tbdosen.nip")
        ->where("tbajardosen.kelas", $kelas)
        ->where("tbajardosen.hari", $hari)
        ->where("tbajardosen.jamAjar", $jamId)
        ->where("tahunAkademik", "LIKE", $tahunAkademik . "%")
        ->where("tbajardosen.nip", "!=", $nip)
        ->select("tbdosen.nama", "tbajardosen.kodeMk")
        ->first();

      if ($bentrokKelas) {
        return redirect()
          ->back()
          ->withInput()
          ->withErrors([
            "kelas" => "Batal simpan! Ruang Kelas {$kelas} sudah terisi oleh {$bentrokKelas->nama} pada Hari {$hari}, Jam Ke-{$labelJam}.",
          ]);
      }
    }

    DB::beginTransaction();
    try {
      foreach ($arrayJam as $index => $jamAjar) {
        if (
          $barisDefaultTersedia->count() > 0 &&
          isset($barisDefaultTersedia[$index])
        ) {
          $idTarget = $barisDefaultTersedia[$index]->id;
          DB::table("tbajardosen")
            ->where("id", $idTarget)
            ->update([
              "hari" => $hari,
              "jamAjar" => (string) $jamAjar,
            ]);
        } else {
          DB::table("tbajardosen")->insert([
            "nip" => $nip,
            "kelas" => $kelas,
            "kodeMk" => $kodeMk,
            "hari" => $hari,
            "jamAjar" => (string) $jamAjar,
            "tahunAkademik" => $tahunAkademik,
          ]);
        }
      }
      DB::commit();
      return redirect()
        ->back()
        ->with(
          "success",
          "Penyusunan jadwal jam mengajar dosen berhasil disimpan!"
        );
    } catch (\Exception $e) {
      DB::rollBack();
      return redirect()
        ->back()
        ->withInput()
        ->withErrors([
          "error" => "Terjadi kesalahan sistem: " . $e->getMessage(),
        ]);
    }
  }

  /**
   * Reset Jadwal Mengajar ke Status Default
   */
  public function destroy(Request $request)
  {
    $request->validate([
      "kelas" => "required|string",
      "kodeMk" => "required|string",
      "hari" => "required|string",
      "tahunAkademik" => "required|string",
    ]);

    try {
      DB::table("tbajardosen")
        ->where("kelas", $request->kelas)
        ->where("kodeMk", $request->kodeMk)
        ->where("hari", $request->hari)
        ->where(
          "tahunAkademik",
          "LIKE",
          substr(trim($request->tahunAkademik), 0, 5) . "%"
        )
        ->update([
          "hari" => "-",
          "jamAjar" => "0",
        ]);

      return redirect()
        ->back()
        ->with(
          "success",
          "Jadwal mengajar kelompok baris tersebut berhasil direset ke status default!"
        );
    } catch (\Exception $e) {
      return redirect()
        ->back()
        ->withErrors(["error" => "Gagal mereset data: " . $e->getMessage()]);
    }
  }

  /**
   * Export / Template Excel Ajar Dosen
   */
  public function exportExcel(Request $request)
  {
    $setting = DB::table("tbsetting")->first();
    $tahunAkademikAktif = $setting
      ? substr(trim($setting->ta_aktif), 0, 5)
      : date("Y") . "1";

    $rows = DB::table("tbajardosen as a")
      ->leftJoin("tbdosen as d", "a.nip", "=", "d.nip")
      ->leftJoin("tbkurikulum as k", "a.kodeMk", "=", "k.kodeMk")
      ->leftJoin("tbjamajar as j", function ($join) {
        $join
          ->on("a.jamAjar", "=", DB::raw("CAST(j.id AS CHAR)"))
          ->on("a.hari", "=", "j.hari");
      })
      ->select([
        DB::raw("COALESCE(d.singkatan, d.nama, a.nip) as singkatan_dosen"),
        "a.kelas",
        DB::raw("COALESCE(k.singkatan, k.namaMk, a.kodeMk) as singkatan_mk"),
        "a.hari",
        DB::raw("COALESCE(j.jamNormal, a.jamAjar) as jam_normal"),
        "a.tahunAkademik",
      ])
      ->where("a.tahunAkademik", "LIKE", $tahunAkademikAktif . "%")
      ->orderBy("d.nama", "asc")
      ->limit(10)
      ->get()
      ->map(fn($item) => (array) $item)
      ->toArray();

    if (empty($rows)) {
      $sampleDosen =
        DB::table("tbdosen")
          ->whereNotNull("singkatan")
          ->value("singkatan") ??
        (DB::table("tbdosen")->value("nama") ?? "DSN");

      $sampleMk =
        DB::table("tbkurikulum")
          ->whereNotNull("singkatan")
          ->value("singkatan") ??
        (DB::table("tbkurikulum")->value("namaMk") ?? "MK");

      $sampleJam =
        DB::table("tbjamajar")
          ->where("hari", "Senin")
          ->value("jamNormal") ?? "1";

      $rows = [
        [
          "singkatan_dosen" => $sampleDosen,
          "kelas" => "1AA",
          "singkatan_mk" => $sampleMk,
          "hari" => "Senin",
          "jam_normal" => $sampleJam,
          "tahunAkademik" => $tahunAkademikAktif,
        ],
      ];
    }

    $writer = SimpleExcelWriter::streamDownload(
      "template_ajar_dosen_" . $tahunAkademikAktif . ".xlsx"
    );
    $writer->addRows($rows);

    return $writer->toBrowser();
  }

  /**
   * Import Data Mengajar dari Excel (Dengan Validasi Pengecekan Bentrok Jam Ajar)
   */
  public function importExcel(Request $request)
  {
    $request->validate([
      "file_excel" => "required|mimes:xlsx,xls,csv|max:10240",
    ]);

    $file = $request->file("file_excel");
    $reader = SimpleExcelReader::create(
      $file->getPathname(),
      $file->getClientOriginalExtension()
    );

    // Cache Master Data
    $listDosen = DB::table("tbdosen")
      ->select("nip", "nama", "singkatan")
      ->get();
    $listMk = DB::table("tbkurikulum")
      ->select("kodeMk", "namaMk", "singkatan")
      ->get();
    $listKelas = DB::table("tbkelas")
      ->select("namaKelas", "kodeProgram")
      ->get();
    $listJam = DB::table("tbjamajar")
      ->select("id", "hari", "jamNormal", "jamRamadan", "kodeProgram")
      ->get();

    $insertCount = 0;
    $skippedCount = 0;

    $reader
      ->getRows()
      ->each(function (array $row) use (
        &$insertCount,
        &$skippedCount,
        $listDosen,
        $listMk,
        $listKelas,
        $listJam
      ) {
        // Raw Inputs
        $singkatanDosenInput = trim(
          (string) ($row["singkatan_dosen"] ??
            ($row["dosen"] ?? ($row["nip"] ?? "")))
        );
        $kelas = strtoupper(
          trim(preg_replace("/\s+/", " ", $row["kelas"] ?? ""))
        );
        $singkatanMkInput = trim(
          (string) ($row["singkatan_mk"] ??
            ($row["matakuliah"] ?? ($row["kodeMk"] ?? "")))
        );
        $hari = ucfirst(strtolower(trim($row["hari"] ?? "")));
        $jamNormalInput = trim(
          (string) ($row["jam_normal"] ??
            ($row["jamNormal"] ?? ($row["jamAjar"] ?? ($row["jam"] ?? ""))))
        );
        $tahunAkademik = substr(
          trim((string) ($row["tahunAkademik"] ?? ($row["ta"] ?? ""))),
          0,
          5
        );

        // 1. Cek kelengkapan kolom Excel
        if (
          empty($singkatanDosenInput) ||
          empty($kelas) ||
          empty($singkatanMkInput) ||
          empty($hari) ||
          empty($jamNormalInput) ||
          empty($tahunAkademik)
        ) {
          Log::warning("Import Excel Skipped (Kolom Kosong)", $row);
          $skippedCount++;
          return;
        }

        // ==========================================
        // 2. LOOKUP DOSEN
        // ==========================================
        $cleanDosenInput = strtolower(
          trim(preg_replace("/[^a-zA-Z0-9]/", "", $singkatanDosenInput))
        );

        $dosenMatch = $listDosen->first(function ($d) use (
          $singkatanDosenInput,
          $cleanDosenInput
        ) {
          $singkatanDb = strtolower(
            trim(preg_replace("/[^a-zA-Z0-9]/", "", $d->singkatan ?? ""))
          );
          $namaDb = strtolower(
            trim(preg_replace("/[^a-zA-Z0-9]/", "", $d->nama ?? ""))
          );
          $nipDb = trim($d->nip ?? "");

          if (!empty($singkatanDb) && $singkatanDb === $cleanDosenInput) {
            return true;
          }
          if ($nipDb === $singkatanDosenInput) {
            return true;
          }
          if (!empty($namaDb) && $namaDb === $cleanDosenInput) {
            return true;
          }
          if (
            strlen($cleanDosenInput) >= 3 &&
            str_starts_with($namaDb, $cleanDosenInput)
          ) {
            return true;
          }

          return false;
        });

        if (!$dosenMatch) {
          Log::warning(
            "Import Excel Skipped: Dosen tidak cocok [{$singkatanDosenInput}]"
          );
          $skippedCount++;
          return;
        }

        // ==========================================
        // 3. LOOKUP MATA KULIAH
        // ==========================================
        $cleanMkInput = strtolower(
          trim(preg_replace("/[^a-zA-Z0-9]/", "", $singkatanMkInput))
        );

        $mkMatch = $listMk->first(function ($m) use (
          $singkatanMkInput,
          $cleanMkInput
        ) {
          $singkatanDb = strtolower(
            trim(preg_replace("/[^a-zA-Z0-9]/", "", $m->singkatan ?? ""))
          );
          $namaDb = strtolower(
            trim(preg_replace("/[^a-zA-Z0-9]/", "", $m->namaMk ?? ""))
          );
          $kodeMkDb = strtolower(trim($m->kodeMk ?? ""));

          return ($singkatanDb !== "" && $singkatanDb === $cleanMkInput) ||
            ($kodeMkDb !== "" && $kodeMkDb === strtolower($singkatanMkInput)) ||
            ($namaDb !== "" && $namaDb === $cleanMkInput);
        });

        if (!$mkMatch) {
          Log::warning(
            "Import Excel Skipped: MK tidak cocok [{$singkatanMkInput}]"
          );
          $skippedCount++;
          return;
        }

        // ==========================================
        // 4. LOOKUP KELAS
        // ==========================================
        $kelasMatch = $listKelas->first(function ($k) use ($kelas) {
          return strcasecmp(trim($k->namaKelas), $kelas) === 0;
        });

        if (!$kelasMatch) {
          Log::warning("Import Excel Skipped: Kelas tidak cocok [{$kelas}]");
          $skippedCount++;
          return;
        }
        $kodeProgramKelas = $kelasMatch->kodeProgram;

        // ==========================================
        // 5. LOOKUP JAM AJAR
        // ==========================================
        $cleanJamInput = preg_replace("/\s*-\s*/", "-", trim($jamNormalInput));
        $cleanJamInput = str_replace(".", ":", $cleanJamInput);

        $jamMatches = $listJam->filter(function ($j) use (
          $hari,
          $cleanJamInput
        ) {
          $matchHari = strcasecmp(trim($j->hari), trim($hari)) === 0;

          $jamDb = preg_replace("/\s*-\s*/", "-", trim($j->jamNormal ?? ""));
          $ramadanDb = preg_replace(
            "/\s*-\s*/",
            "-",
            trim($j->jamRamadan ?? "")
          );

          $matchJam =
            strcasecmp($jamDb, $cleanJamInput) === 0 ||
            strcasecmp($ramadanDb, $cleanJamInput) === 0 ||
            (string) $j->id === $cleanJamInput;

          return $matchHari && $matchJam;
        });

        $jamMatch = null;
        if ($kodeProgramKelas) {
          $jamMatch = $jamMatches->first(function ($j) use ($kodeProgramKelas) {
            return !empty($j->kodeProgram) &&
              strcasecmp(trim($j->kodeProgram), $kodeProgramKelas) === 0;
          });
        }

        if (!$jamMatch) {
          $jamMatch = $jamMatches->first();
        }

        if (!$jamMatch) {
          Log::warning(
            "Import Excel Skipped: Jam Ajar tidak ditemukan untuk hari [{$hari}] jam [{$jamNormalInput}]"
          );
          $skippedCount++;
          return;
        }

        // ==========================================
        // 6. VALIDASI BENTROK JADWAL (DOSEN & KELAS)
        // ==========================================

        // A. Cek Bentrok Dosen (Dosen mengajar di tempat/kelas lain pada hari & jam yang sama)
        $bentrokDosen = DB::table("tbajardosen")
          ->where("nip", $dosenMatch->nip)
          ->where("hari", $hari)
          ->where("jamAjar", (string) $jamMatch->id)
          ->where("tahunAkademik", "LIKE", $tahunAkademik . "%")
          ->where("kelas", "!=", $kelas)
          ->exists();

        if ($bentrokDosen) {
          Log::warning(
            "Import Excel Skipped (Bentrok Dosen): NIP {$dosenMatch->nip} ({$dosenMatch->nama}) sudah ada jadwal mengajar di kelas lain pada {$hari}, Jam ID {$jamMatch->id}"
          );
          $skippedCount++;
          return;
        }

        // B. Cek Bentrok Kelas (Kelas sudah diisi oleh dosen lain pada hari & jam yang sama)
        $bentrokKelas = DB::table("tbajardosen")
          ->where("kelas", $kelas)
          ->where("hari", $hari)
          ->where("jamAjar", (string) $jamMatch->id)
          ->where("tahunAkademik", "LIKE", $tahunAkademik . "%")
          ->where("nip", "!=", $dosenMatch->nip)
          ->exists();

        if ($bentrokKelas) {
          Log::warning(
            "Import Excel Skipped (Bentrok Kelas): Kelas {$kelas} sudah terisi dosen lain pada {$hari}, Jam ID {$jamMatch->id}"
          );
          $skippedCount++;
          return;
        }

        // ==========================================
        // 7. EKSEKUSI SIMPAN
        // ==========================================
        $data = [
          "nip" => $dosenMatch->nip,
          "kelas" => $kelas,
          "kodeMk" => $mkMatch->kodeMk,
          "hari" => $hari,
          "jamAjar" => (string) $jamMatch->id,
          "tahunAkademik" => $tahunAkademik,
        ];

        if (
          !DB::table("tbajardosen")
            ->where($data)
            ->exists()
        ) {
          DB::table("tbajardosen")->insert($data);
          $insertCount++;
        } else {
          Log::info("Import Excel Skipped: Data Duplikat Identik", $data);
          $skippedCount++;
        }
      });

    $reader->close();

    return redirect()
      ->back()
      ->with(
        "success",
        "Import selesai! {$insertCount} data berhasil disimpan. ({$skippedCount} baris dilewati karena bentrok, tidak valid, atau duplikat). Silakan cek storage/logs/laravel.log untuk rincian data yang dilewati."
      );
  }
}
