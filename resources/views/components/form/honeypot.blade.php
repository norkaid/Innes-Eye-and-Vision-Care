{{-- Spam trap: hidden from people and assistive technology; bots that fill it are rejected. --}}
<div class="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
    <label for="website_url">Leave this field empty</label>
    <input type="text" id="website_url" name="website_url" tabindex="-1" autocomplete="off" value="">
    <input type="hidden" name="form_started_at" value="{{ encrypt(now()->timestamp) }}">
</div>
