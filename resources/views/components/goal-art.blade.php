@props(['type' => 'goat'])
<svg class="goal-flat-art" viewBox="0 0 220 200" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <circle cx="110" cy="99" r="82" fill="{{ $type === 'goat' ? '#DCEBCF' : '#F4E6C3' }}"/>
    <path d="M28 174h164" stroke="{{ $type === 'goat' ? '#B8CEA4' : '#DCC695' }}" stroke-width="4" stroke-linecap="round"/>
    @if($type === 'goat')
    <g class="scene-goat">
        <path d="M88 142v28m49-28v28" stroke="#A57850" stroke-width="12" stroke-linecap="round"/>
        <path d="M82 171h13m37 0h13" stroke="#526849" stroke-width="6" stroke-linecap="round"/>
        <path class="goat-tail" d="m159 119 15-15" stroke="#FDF9E9" stroke-width="12" stroke-linecap="round"/>
        <rect x="71" y="103" width="94" height="49" rx="24" fill="#FFFCED"/>
        <path d="M69 87q-16-23-9-34m32 34q14-23 9-34" stroke="#94704F" stroke-width="9" stroke-linecap="round"/>
        <path d="M59 90c-27-8-28 12-11 28l14-13m34-15c28-8 29 12 12 28l-14-13" fill="#BD9166"/>
        <path d="M53 91q25-22 48 0v30q-3 30-24 32-21-2-24-32Z" fill="#F8EBCF"/>
        <g class="goat-eyes" fill="#34523C"><circle cx="66" cy="109" r="3"/><circle cx="88" cy="109" r="3"/></g>
        <ellipse cx="77" cy="128" rx="16" ry="11" fill="#EAD4AD"/>
        <path d="m73 124 4 3 4-3m-4 3v6m-6 0q6 6 12 0" stroke="#8A6749" stroke-width="2.5" stroke-linecap="round"/>
        <path d="m71 147 6 13 6-13" fill="#C6AE83"/>
    </g>
    <g class="scene-sprout"><path d="M37 170v-21" stroke="#628552" stroke-width="3"/><path d="M37 159q-23-2-18-16 16-3 18 16Zm1-9q1-19 18-17 4 14-18 17Z" fill="#8FB36E"/></g>
    @else
    <g class="scene-grocery">
        <path d="M65 114V96a45 45 0 0 1 90 0v18" stroke="#B6884D" stroke-width="8"/>
        <path d="m60 124 5-57h43l6 59" fill="#FFFCED" stroke="#DACBA3" stroke-width="2"/>
        <path d="M71 81h26m-28 9h29" stroke="#D6C59A" stroke-width="3" stroke-linecap="round"/>
        <rect x="72" y="99" width="28" height="19" rx="4" fill="#E0EBCB"/><path d="m80 112 12-8" stroke="#86A35E" stroke-width="3" stroke-linecap="round"/>
        <path d="M123 78h17v16l7 9v30h-31v-30l7-9Z" fill="#EDC55F"/><rect x="122" y="70" width="19" height="10" rx="3" fill="#7B9D64"/><rect x="118" y="112" width="27" height="15" rx="3" fill="#FFF1BA"/>
        <g class="grocery-leaves"><path d="M156 136c-30-30-29-55-13-41 0-31 25-32 23 4 21-27 39-10 18 12 23-4 24 14-28 25Z" fill="#83A663"/><path d="m156 136 6-35m-4 24 16-13m-16 14-13-17" stroke="#547B46" stroke-width="3" stroke-linecap="round"/></g>
        <ellipse cx="79" cy="127" rx="13" ry="17" fill="#E9D0A9"/><ellipse cx="103" cy="124" rx="13" ry="17" fill="#FFF1D6"/>
        <path d="m41 132 14 35q55 19 110 0l14-35Z" fill="#CEA064"/>
        <path d="M56 146h109M62 158h96m-82-20 5 32m28-32v36m28-36-5 32" stroke="#B58851" stroke-width="2"/>
        <rect x="37" y="125" width="146" height="14" rx="7" fill="#E5BB80"/>
        <rect x="93" y="145" width="34" height="23" rx="6" fill="#FFF4DA"/><path d="m101 156 6 5 12-10" stroke="#7F9D5B" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
    </g>
    @endif
    <g class="scene-coin scene-coin-second"><circle cx="169" cy="40" r="16" fill="#F3D17D"/><circle cx="169" cy="40" r="11" stroke="#D3AB52" stroke-width="2"/><path d="M169 34v12m-3-9h5m-5 6h5" stroke="#B48B38" stroke-width="2" stroke-linecap="round"/></g>
    <path class="scene-sparkles" d="M32 73h12m-6-6v12m140 79h10m-5-5v10" stroke="#A9BB7E" stroke-width="3" stroke-linecap="round"/>
</svg>
