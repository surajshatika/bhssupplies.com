@extends('frontend.layouts.app')

@section('meta_title', 'HVAC Supplies Mississauga | Air Conditioning Tools & Plumbing | BHS Supplies')
@section('meta_description', 'Your top-rated HVAC supply store in Mississauga. We provide wholesale heating and cooling supplies, air conditioning tools, and plumbing equipment for contractors.')

@section('content')
<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-lg-8">
            <h1 class="h2 fw-700 text-dark mb-4">HVAC Supplies in Mississauga: Your Local Partner</h1>
            <p class="fs-15 text-dark" style="line-height: 1.8;">
                Welcome to BHS Supplies, the leading destination for professional-grade <strong>HVAC supplies in Mississauga</strong>. Centrally located at 7040 Torbram Rd #8, we are proud to serve the local community of licensed contractors, electricians, and plumbers. We know that sourcing reliable <strong>heating and cooling supplies</strong> quickly is crucial for your business. That's why we carry an extensive inventory of top-tier products ready for immediate pickup.
            </p>

            <h2 class="h4 fw-600 mt-4 mb-3 text-dark">Why Choose BHS for Air Conditioning Tools in Canada?</h2>
            <p class="fs-15 text-dark" style="line-height: 1.8;">
                When you search for an "<strong>HVAC supply store near me</strong>" in Mississauga, you need a supplier that understands the unique demands of the Canadian climate. Our selection of <strong>air conditioning tools in Canada</strong> is unmatched. From precision refrigerant scales and manifold gauges to heavy-duty vacuum pumps, we supply the equipment necessary for efficient installations and repairs.
            </p>

            <h2 class="h4 fw-600 mt-4 mb-3 text-dark">Wholesale HVAC Supplies & Plumbing Essentials</h2>
            <p class="fs-15 text-dark" style="line-height: 1.8;">
                We are more than just an <strong>HVAC tools supplier</strong>. BHS Supplies also offers a comprehensive range of <strong>plumbing and HVAC supplies</strong>. Whether you need specialized Knipex pliers, Wera hand tools, or essential copper and PVC fittings, our <strong>wholesale HVAC supplies</strong> pricing ensures you get the best value. Buy HVAC tools online in Canada through our B2B portal, or visit our Mississauga storefront for expert advice and personalized service.
            </p>
            
            <div class="mt-5 p-4 bg-light rounded border">
                <h3 class="h5 fw-600 mb-3">Visit Our Mississauga Store</h3>
                <ul class="list-unstyled fs-15 text-dark mb-0">
                    <li class="mb-2"><i class="las la-map-marker text-primary mr-2"></i> <strong>Address:</strong> 7040 Torbram Rd #8, Mississauga, ON L4T 3Z4</li>
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
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2884.283424164344!2d-79.6631853!3d43.7046894!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x882b3ebbfdf989b5%3A0xc3f8e6c7d1e8c9b!2s7040%20Torbram%20Rd%20%238%2C%20Mississauga%2C%20ON%20L4T%203Z4!5e0!3m2!1sen!2sca!4v1690000000000!5m2!1sen!2sca" width="100%" height="400" style="border:0; border-radius: 4px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
