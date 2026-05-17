@extends('frontend.layouts.app')

@section('meta_title', 'HVAC Supplies Brampton | Plumbing & AC Tools | BHS Supplies')
@section('meta_description', 'Discover premium HVAC supplies, air conditioning tools, and plumbing equipment in Brampton. BHS Supplies offers wholesale prices for local contractors.')

@section('content')
<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-lg-8">
            <h1 class="h2 fw-700 text-dark mb-4">Top-Quality HVAC Supplies in Brampton</h1>
            <p class="fs-15 text-dark" style="line-height: 1.8;">
                Contractors and tradespeople in Brampton know that having the right tools is essential for success. <strong>BHS Supplies</strong> provides a massive inventory of <strong>HVAC supplies in Brampton</strong> and the surrounding areas. From residential heating repairs to commercial cooling installations, our <strong>heating and cooling supplies</strong> are designed to meet the rigorous standards of professional trades.
            </p>

            <h2 class="h4 fw-600 mt-4 mb-3 text-dark">Your Trusted HVAC Supply Store Near Me</h2>
            <p class="fs-15 text-dark" style="line-height: 1.8;">
                Stop searching endlessly for an "<strong>HVAC supply store near me</strong>." BHS Supplies is conveniently situated to serve the Brampton community. We stock the finest <strong>air conditioning tools in Canada</strong>, ensuring you have immediate access to high-quality vacuum pumps, gauges, copper tubing, and refrigerant recovery machines.
            </p>

            <h2 class="h4 fw-600 mt-4 mb-3 text-dark">Wholesale Plumbing and HVAC Supplies</h2>
            <p class="fs-15 text-dark" style="line-height: 1.8;">
                We are proud to be a leading <strong>HVAC tools supplier</strong> offering aggressive <strong>wholesale HVAC supplies</strong> pricing for B2B accounts. Our extensive catalog covers all your <strong>plumbing and HVAC supplies</strong> needs, featuring top brands like Knipex and Wera. Buy HVAC tools online in Canada with confidence, or drop by to pick up your supplies fast and get back to the job site.
            </p>
            
            <div class="mt-5 p-4 bg-light rounded border">
                <h3 class="h5 fw-600 mb-3">Supporting Brampton Contractors</h3>
                <ul class="list-unstyled fs-15 text-dark mb-0">
                    <li class="mb-2"><i class="las la-hard-hat text-primary mr-2"></i> <strong>For Pros:</strong> Exclusive B2B pricing and bulk discounts.</li>
                    <li class="mb-2"><i class="las la-phone text-primary mr-2"></i> <strong>Phone:</strong> +1 (647) 456-2244</li>
                    <li class="mb-0"><i class="las la-envelope text-primary mr-2"></i> <strong>Email:</strong> support@bhssupplies.com</li>
                </ul>
                <div class="mt-4">
                    <a href="{{ route('search') }}" class="btn btn-primary fw-600">Shop All Products</a>
                    <a href="{{ route('contact') }}" class="btn btn-outline-primary fw-600 ml-2">Contact Us</a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                     <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2884.0921200185973!2d-79.7618146!3d43.7182412!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x882b15f5c35390ed%3A0x6e255ddb1d198118!2sBrampton%2C%20ON!5e0!3m2!1sen!2sca!4v1690000000002!5m2!1sen!2sca" width="100%" height="400" style="border:0; border-radius: 4px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
