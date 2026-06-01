@if($exam->student_action === 'view_result')
    <a href="{{ route('student.results') }}" class="inline-flex justify-center rounded-2xl bg-green-600 px-4 py-3 text-sm font-black text-white shadow-sm hover:bg-green-700">Lihat Hasil</a>
@elseif($exam->student_action === 'continue_exam')
    <a href="{{ route('student.exams.start', $exam) }}" class="inline-flex justify-center rounded-2xl bg-amber-500 px-4 py-3 text-sm font-black text-white shadow-sm hover:bg-amber-600">Lanjutkan Ujian</a>
@elseif($exam->student_action === 'enter_token')
    <button type="button" @click="tokenExam = {{ $exam->id }}" class="rounded-2xl bg-[#2563eb] px-4 py-3 text-sm font-black text-white shadow-sm hover:bg-blue-700">Masukkan Token</button>
    <div x-show="tokenExam === {{ $exam->id }}" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" style="display:none;">
        <form action="{{ route('student.exams.token', $exam) }}" method="post" class="w-full max-w-md rounded-[2rem] bg-white p-6 shadow-2xl" @click.outside="tokenExam = null">
            @csrf
            <div class="mb-5">
                <p class="text-sm font-bold uppercase tracking-[0.2em] text-[#2563eb]">Token Ujian</p>
                <h3 class="mt-2 text-2xl font-black text-slate-950">{{ $exam->nama_ujian }}</h3>
                <p class="mt-1 text-sm font-semibold text-slate-500">Masukkan token dari pengawas untuk mulai ujian.</p>
            </div>
            @error('token_'.$exam->id)<div class="mb-4 rounded-2xl bg-red-50 p-3 text-sm font-bold text-red-600">{{ $message }}</div>@enderror
            <input name="token" value="{{ old('token') }}" maxlength="20" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-center text-lg font-black uppercase tracking-[0.3em] outline-none focus:border-[#2563eb] focus:ring-4 focus:ring-blue-100" placeholder="TOKEN">
            <div class="mt-5 flex gap-3">
                <button type="button" @click="tokenExam = null" class="flex-1 rounded-2xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600">Batal</button>
                <button class="flex-1 rounded-2xl bg-[#2563eb] px-4 py-3 text-sm font-black text-white">Validasi</button>
            </div>
        </form>
    </div>
@elseif($exam->student_action === 'start_exam')
    <a href="{{ route('student.exams.start', $exam) }}" class="inline-flex justify-center rounded-2xl bg-[#2563eb] px-4 py-3 text-sm font-black text-white shadow-sm hover:bg-blue-700">Mulai Ujian</a>
@elseif($exam->student_status === 'upcoming')
    <button disabled class="rounded-2xl bg-slate-200 px-4 py-3 text-sm font-black text-slate-500">Belum Dibuka</button>
@elseif($exam->student_status === 'not_ready')
    <button disabled class="rounded-2xl bg-slate-200 px-4 py-3 text-sm font-black text-slate-500">Ujian Belum Siap</button>
@else
    <button disabled class="rounded-2xl bg-slate-200 px-4 py-3 text-sm font-black text-slate-500">{{ $exam->status_label }}</button>
@endif
