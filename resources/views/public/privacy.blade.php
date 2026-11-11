<x-layouts.public title="Privacy notice" description="How {{ $site->get('business_name') }} handles personal information submitted through this website.">
    <x-page-header title="Privacy notice" eyebrow="Your information" />
    <section class="container-page max-w-3xl py-14">
        <x-alert type="warning" title="Draft for business and legal review">
            This notice is draft wording prepared for review. It must be checked by the centre and an appropriate professional against Ghana's Data Protection Act, 2012 (Act 843) and any applicable healthcare requirements before the website goes live.
        </x-alert>
        <div class="prose-content mt-8">
            <h2>What we collect</h2>
            <p>When you request an appointment we collect your name, email address, phone number, preferred branch, service, date and time, and any short note you choose to add. When you send an enquiry we collect your name, email address, optional phone number and your message. If you create an account we also store a securely hashed password.</p>
            <p><strong>Please do not submit detailed medical information</strong> (such as diagnoses, medication or medical history) through this website. Your optometrist will discuss your eye health with you in person.</p>
            <h2>How we use it</h2>
            <ul>
                <li>To review and respond to your appointment request or enquiry.</li>
                <li>To contact you about the status of your request.</li>
                <li>To keep basic records of appointment requests and enquiries for administration.</li>
            </ul>
            <p>We do not sell your information or use it for unrelated marketing.</p>
            <h2>Who can see it</h2>
            <p>Only authorised staff whose role requires it can access your information. Access is controlled by role-based permissions and actions are logged.</p>
            <h2>How long we keep it</h2>
            <p>[Retention period to be confirmed by the centre.]</p>
            <h2>Your rights</h2>
            <p>You may ask to see, correct or delete the personal information we hold about you. Please use the <a href="{{ route('contact') }}">contact form</a> or contact your branch.</p>
            <h2>Cookies</h2>
            <p>This website uses essential cookies only, to keep you signed in and to protect forms against misuse.</p>
            <h2>Consent wording used on forms</h2>
            <blockquote>I agree to {{ $site->get('business_name') }} using the details I have provided to handle this request, as described in the privacy notice.</blockquote>
        </div>
    </section>
</x-layouts.public>
