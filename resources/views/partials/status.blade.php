@if($r->is_official)
    <span class="chip green"><x-icon name="graduation-cap" :size="12" /> Official</span>
@elseif($r->status === 'verified')
    <span class="chip green"><x-icon name="check-circle" :size="12" /> Verified</span>
@elseif($r->status === 'rejected')
    <span class="chip red"><x-icon name="x-circle" :size="12" /> Rejected</span>
@else
    <span class="chip amber"><x-icon name="clock" :size="12" /> Awaiting review</span>
@endif
