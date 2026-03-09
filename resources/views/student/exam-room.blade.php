@extends('layouts.app')
@section('content')
<div class="bg-white rounded-xl shadow p-5" x-data="examRoom({{ $result->id }})" x-init="init()">
    <h2 class="font-semibold mb-4">{{ $result->exam->nama_ujian }}</h2>
    @foreach($answers as $item)
        <div class="border-t py-4">
            <p class="mb-2">{{ $loop->iteration }}. {{ $item->question->soal }}</p>
            @foreach(['a','b','c','d','e'] as $opt)
                @php($field = 'pilihan_'.$opt)
                @if($item->question->$field)
                    <button @click="save({{ $item->question_id }}, '{{ $opt }}')" class="block text-left w-full mb-1 rounded-lg border p-2">{{ strtoupper($opt) }}. {{ $item->question->$field }}</button>
                @endif
            @endforeach
        </div>
    @endforeach
    <form method="post" action="{{ route('student.exams.submit', $result) }}">@csrf<button class="mt-4 bg-green-600 text-white px-4 py-2 rounded-xl">Selesai Ujian</button></form>
</div>
<script>
function examRoom(resultId){
    return {
        init(){
            document.documentElement.requestFullscreen?.();
            document.addEventListener('visibilitychange',()=>{ if(document.hidden){ this.cheat('tab_switch'); } });
            document.addEventListener('fullscreenchange',()=>{ if(!document.fullscreenElement){ alert('Harap tetap fullscreen'); this.cheat('fullscreen_exit'); } });
            ['contextmenu','copy','paste'].forEach(evt=>document.addEventListener(evt,e=>e.preventDefault()));
            window.addEventListener('beforeunload', (e)=>{ e.preventDefault(); e.returnValue=''; });
        },
        async save(qid,ans){
            await fetch(`/student/room/${resultId}/answer`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({question_id:qid,jawaban:ans})});
        },
        async cheat(type){
            await fetch(`/student/room/${resultId}/cheating-log`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({type})});
        }
    }
}
</script>
@endsection
