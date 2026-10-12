@if(session('success'))<div class="alert" role="status"><i data-lucide="circle-check"></i>{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert error" role="alert"><strong>Periksa kembali data Anda</strong><ul class="mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
