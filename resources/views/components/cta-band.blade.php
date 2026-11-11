<section class="bg-navy" aria-labelledby="cta-heading">
    <div class="container-page flex flex-col items-start justify-between gap-6 py-12 md:flex-row md:items-center">
        <div>
            <h2 id="cta-heading" class="font-display text-2xl font-bold text-white sm:text-3xl">Ready to look after your eyes?</h2>
            <p class="mt-2 max-w-xl text-white/85">Request an appointment online and our team will contact you to confirm a time.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('appointments.create') }}" class="btn-primary">Book Appointment</a>
            <a href="{{ route('contact') }}" class="btn border-2 border-white text-white hover:bg-white hover:text-navy">Contact us</a>
        </div>
    </div>
</section>
