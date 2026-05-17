@extends('frontend.layouts.app')

@section('meta_title', 'HVAC Supplies Toronto | Heating & Cooling Tools | BHS Supplies')
@section('meta_description', 'BHS Supplies is your trusted provider for HVAC supplies, plumbing equipment, and air conditioning tools in Toronto. Best wholesale prices for contractors.')

@section('content')
<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-lg-8">
            <h1 class="h2 fw-700 text-dark mb-4">HVAC Supplies in Toronto: Professional Tools & Equipment</h1>
            <p class="fs-15 text-dark" style="line-height: 1.8;">
                Are you a contractor working in the Greater Toronto Area? <strong>BHS Supplies</strong> is your reliable source for premium <strong>HVAC supplies in Toronto</strong>. We understand that maneuvering through the city to find the right parts can be time-consuming. That's why we offer a comprehensive inventory of <strong>heating and cooling supplies</strong>, ensuring you have exactly what you need, exactly when you need it.
            </p>

            <h2 class="h4 fw-600 mt-4 mb-3 text-dark">Top-Rated Air Conditioning Tools in Canada</h2>
            <p class="fs-15 text-dark" style="line-height: 1.8;">
                From high-rise condos to residential homes, Toronto contractors demand the best <strong>air conditioning tools in Canada</strong>. At BHS Supplies, we stock industry-leading brands like Knipex and Wera. If you're looking for an <strong>HVAC supply store near me</strong> that caters specifically to Toronto's fast-paced construction and repair industry, our extensive selection of vacuum pumps, recovery units, and diagnostic gauges won't disappoint.
            </p>

            <h2 class="h4 fw-600 mt-4 mb-3 text-dark">Wholesale Plumbing and HVAC Supplies</h2>
            <p class="fs-15 text-dark" style="line-height: 1.8;">
                As a premier <strong>HVAC tools supplier</strong>, we cater to B2B clients by offering highly competitive <strong>wholesale HVAC supplies</strong>. Whether your project requires heavy-duty <strong>plumbing and HVAC supplies</strong> or specialized hand tools, you can buy HVAC tools online in Canada through our secure portal and get them delivered to your Toronto job site, or visit our nearby Mississauga location for immediate pickup.
            </p>
            
            <div class="mt-5 p-4 bg-light rounded border">
                <h3 class="h5 fw-600 mb-3">Serving the Greater Toronto Area</h3>
                <ul class="list-unstyled fs-15 text-dark mb-0">
                    <li class="mb-2"><i class="las la-truck text-primary mr-2"></i> <strong>Delivery:</strong> Fast delivery to job sites across Toronto.</li>
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
                     <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2887.279611685816!2d-79.3831843!3d43.653226!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89d4cb34095c1145%3A0x1d36186b59d0426b!2sToronto%2C%20ON!5e0!3m2!1sen!2sca!4v1690000000001!5m2!1sen!2sca" width="100%" height="400" style="border:0; border-radius: 4px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
