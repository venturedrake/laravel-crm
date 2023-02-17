@extends('laravel-crm::layouts.document')

@section('content')

    @include('laravel-crm::quotes.partials.document')

@endsection

{{--
@section('content')
    
   <div class="row">
        <div class="col-12">
            <strong>Application #:</strong>
            <span>593</span>
        </div>
    </div>
    <h3>Section I: Applicant Information</h3>
    <table class="table table-bordered table-condensed">
        <tbody>

        <tr>
            <td>
                <h6>
                    <strong>Applicant name</strong>
                </h6>
                <span>Doe, John</span>
            </td>
        </tr>
        <tr>
            <td>
                <h6>
                    <strong>Email address</strong>
                </h6>
                <span>support@api2pdf.com</span>
            </td>
        </tr>
        <tr>
            <td>
                <h6>
                    <strong>Job title</strong>
                </h6>
                <span>Senior Software Engineer</span>
            </td>
        </tr>
        <tr>
            <td>
                <h6>
                    <strong>Company name</strong>
                </h6>
                <span>Api2Pdf</span>
            </td>
        </tr>
        <tr>
            <td>
                <h6>
                    <strong>Company address</strong>
                </h6>
                <span>44 Fake St, Arlington, VA, 00000</span>
            </td>
        </tr>
        <tr>
            <td>
                <h6>
                    <strong>Company website</strong>
                </h6>
                <span>
                            <a href="https://www.api2pdf.com">https://www.api2pdf.com</span>
            </td>
        </tr>
        <tr>
            <td>
                <h6>
                    <strong>Company twitter</strong>
                </h6>
                <span>
                            <a href="https://www.twitter.com/api2pdf_status">https://www.twitter.com/api2pdf_status</span>
            </td>
        </tr>
        <tr>
            <td>
                <h6>
                    <strong>Phone #</strong>
                </h6>
                <span>555-555-5555</span>
            </td>
        </tr>
        </tbody>
    </table>
    <hr/>
    <h4>Section II: Entry Information</h4>

    <table class="table table-bordered table-condensed">
        <tbody>
        <tr>
            <td>
                <h6>
                    <strong>Product name</strong>
                </h6>
                <span>Api2Pdf</span>
            </td>
        </tr>
        <tr>
            <td>
                <h6>
                    <strong>Brief product description</strong>
                </h6>
                <span>Api2Pdf</span>
            </td>
        </tr>
        <tr>
            <td>
                <h6>
                    <strong>Why did you create this product?</strong>
                </h6>
                <span>
                            <p>Api2Pdf is a REST API that helps application developers generate PDFs at massive scale. It was
                                co-founded by myself, Zack Schwartz, and my partner Kunal Johar. The two of us also run another
                                company called
                                <a href="https://www.getopenwater.com">OpenWater</a>. OpenWater’s customers live and die by PDFs. And they are not normal PDFs either.
                                Often times, the PDFs are hundreds of pages long and contain high-res photos. As our customer
                                base grew, our costs to generate PDFs ballooned. Eventually, we had a whole server dedicated
                                to producing PDFs and nothing else. Scaled all the way up, it was costing us over $1000 a
                                month.</p>
                            <p>That server eventually crashed too, and customers couldn’t generate PDFs anymore. One of our
                                customers relying on this capability had a deadline to send the PDFs to their publisher for
                                printing the next day for their main event of the year.</p>
                            <p>In response to this meltdown, we needed a completely fresh approach. </p>
                            <p>We knew from the get-go that our stack had to be fast and cheap. We love .NET and wanted to explore
                                the brand new .NET Core. What attracted us to .NET Core was how lightweight and fast it is,
                                and that it’s cross platform. Our web portal and API key management is completely written
                                in .NET Core. Our logs are stored in Azure Table Storage. We considered a Python + Flask
                                stack, but quickly dismissed the idea.</p>
                            <p>To keep costs down, PDF generation had to be built on a serverless architecture. Our API endpoints
                                are built in .NET on Azure Functions, and handles all of the incoming requests. These requests,
                                should they be valid, are then forwarded on to AWS Lambda for PDF generation. AWS Lambda
                                is the serverless architecture that allows us to scale to millions of requests at very low
                                cost. The PDFs themselves are stored on Amazon S3.</p>
                            <p>When we began using this internally, OpenWater’s costs went from $1000 per month to $60 per month
                                and had no downtime whatsoever. We realized we built a solid product and decided to retool
                                it so that any developer out there can use it, and that’s why we launched Api2Pdf as its
                                own company.</p>
                            <p>Our Api2Pdf customers tend to find us for one of two reasons. First, just trying to get any of
                                the PDF generating libraries like wkhtmltopdf or Headless Chrome working in a cloud environment
                                can ruin your day. And second, PDF generation is quite CPU intensive, and if you need to
                                generate thousands of them, the costs to have a dedicated server soley for PDFs will skyrocket
                                as what happened to us.</p>
                            <p>The most common use-case is to convert HTML to PDF for the purpose of printing invoices, event
                                tickets, resumes, packing slips, etc, but we also provide endpoints for converting Microsoft
                                Office documents to PDF and merging multiple PDFs together. We have such a wide variety of
                                customers, ranging from online clothing stores to web design companies.</p>
                            <p>If you’re a new .NET startup, using Microsoft Azure as your cloud hosting environment is a no-brainer.
                                .NET Core is great, but it is also still very new and currently missing some key functionality.
                                We decided it was safe to use since our web portal is a small app and relatively low risk.
                                But the best advice I can give to new startups is that you just have to grind, day in, and
                                day out. Chart your path and avoid distractions. </p>
                            <p>Api2Pdf is growing rapidly. We have five team members that contribute, all .NET developers. We
                                are excited to make some noise is this oddly specific niche which is PDF generation. But
                                it has been a lot of fun and we are learning something new every day.
                                </p>
                        </span>

            </td>
        </tr>
        </tbody>
    </table>
    <hr/>
    <h4>Section III: Supplemental Materials</h4>

    <table class="table table-bordered table-condensed">
        <tbody>
        <tr>
            <td>
                <h6>
                    <strong>Images</strong>
                </h6>
                <img src="data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20200%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1665080a485%20text%20%7B%20fill%3Argba(255%2C255%2C255%2C.75)%3Bfont-weight%3Anormal%3Bfont-family%3AHelvetica%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1665080a485%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23777%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2274.4296875%22%20y%3D%22104.5%22%3E200x200%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E" class="img-thumbnail">
                <img src="data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20200%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1665080a485%20text%20%7B%20fill%3Argba(255%2C255%2C255%2C.75)%3Bfont-weight%3Anormal%3Bfont-family%3AHelvetica%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1665080a485%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23777%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2274.4296875%22%20y%3D%22104.5%22%3E200x200%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E" class="img-thumbnail">
                <img src="data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20200%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1665080a485%20text%20%7B%20fill%3Argba(255%2C255%2C255%2C.75)%3Bfont-weight%3Anormal%3Bfont-family%3AHelvetica%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1665080a485%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23777%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2274.4296875%22%20y%3D%22104.5%22%3E200x200%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E" class="img-thumbnail">
                <img src="data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20200%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1665080a485%20text%20%7B%20fill%3Argba(255%2C255%2C255%2C.75)%3Bfont-weight%3Anormal%3Bfont-family%3AHelvetica%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1665080a485%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23777%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2274.4296875%22%20y%3D%22104.5%22%3E200x200%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E" class="img-thumbnail">
                <img src="data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20200%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1665080a485%20text%20%7B%20fill%3Argba(255%2C255%2C255%2C.75)%3Bfont-weight%3Anormal%3Bfont-family%3AHelvetica%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1665080a485%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23777%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2274.4296875%22%20y%3D%22104.5%22%3E200x200%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E" class="img-thumbnail">
                <img src="data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20200%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1665080a485%20text%20%7B%20fill%3Argba(255%2C255%2C255%2C.75)%3Bfont-weight%3Anormal%3Bfont-family%3AHelvetica%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1665080a485%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23777%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2274.4296875%22%20y%3D%22104.5%22%3E200x200%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E" class="img-thumbnail">
                <img src="data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20200%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1665080a485%20text%20%7B%20fill%3Argba(255%2C255%2C255%2C.75)%3Bfont-weight%3Anormal%3Bfont-family%3AHelvetica%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1665080a485%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23777%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2274.4296875%22%20y%3D%22104.5%22%3E200x200%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E" class="img-thumbnail">
                <img src="data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20200%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1665080a485%20text%20%7B%20fill%3Argba(255%2C255%2C255%2C.75)%3Bfont-weight%3Anormal%3Bfont-family%3AHelvetica%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1665080a485%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23777%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2274.4296875%22%20y%3D%22104.5%22%3E200x200%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E" class="img-thumbnail">
            </td>
        </tr>

        </tbody>
    </table>
    
@endsection
--}}
