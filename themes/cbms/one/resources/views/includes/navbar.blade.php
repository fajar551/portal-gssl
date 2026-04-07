<style>
    @import url('https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css') (scope: #custom-navbar);

    #custom-navbar .fas,
    #custom-navbar .far,
    #custom-navbar .fab {
        display: inline-block;
        font-family: "Font Awesome 5 Free" !important;
    }

    #custom-navbar .fas {
        font-weight: 900 !important;
    }

    #custom-navbar .far {
        font-weight: 400 !important;
    }

    #custom-navbar .fab {
        font-family: "Font Awesome 5 Brands" !important;
    }

    /* Override font-family hanya untuk teks non-icon */
    #custom-navbar *:not(.fas):not(.far):not(.fab) {
        font-family: "DM Sans", sans-serif;

    }


    #custom-navbar .special-text {
        font-family: different-font, sans-serif;
    }

    /* Style lainnya tetap sama dengan prefix #custom-navbar */
    #custom-navbar #page-topbardefault {
        position: fixed;
        top: 0;
        right: 0;
        left: 0;
        z-index: 999;
        background: white;
    }

    #custom-navbar .navbar-nav .nav-link {
        color: #f7863b !important;
    }

    /* Tambahkan di bagian style */
    #productCards {
        display: none;
        position: fixed;
        z-index: 1000;
        width: 100%;
        right: 100px;
        top: 80px;
        transition: all 0.3s ease;
    }

    #productCards.show {
        display: block;
    }

    /* Atur posisi relatif untuk parent */
    .navbar-header {
        position: relative;
    }

    /* Tampilkan menu di desktop */
    @media (min-width: 992px) {
        .navbar-collapse {
            display: flex !important;
            flex-basis: auto;
        }

        .navbar-nav {
            flex-direction: row !important;
            margin-left: auto;
        }


    }

    /* Styling untuk burger icon */
    .navbar-toggler {
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        background-color: transparent;
    }

    .navbar-toggler:focus {
        outline: none;
        box-shadow: none;
    }

    /* Styling untuk warna link navbar */
    .navbar-nav .nav-link {
        color: #f7863b !important;
    }

    /* Styling untuk warna link dropdown */
    .dropdown-menu .dropdown-item {
        color: #f7863b !important;
    }

    /* Optional: Styling untuk hover state */
    .navbar-nav .nav-link:hover,
    .dropdown-menu .dropdown-item:hover {
        color: #d66d2f !important;
        /* Sedikit lebih gelap untuk hover state */
    }

    /* Custom burger icon */
    .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%280, 0, 0, 0.55%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        display: inline-block;
        width: 1.5em;
        height: 1.5em;
        vertical-align: middle;
    }

    /* Sembunyikan burger menu di desktop */
    @media (min-width: 992px) {
        .navbar-toggler {
            display: none;
        }
    }

    /* Styling untuk dropdown menu */
    .dropdown-menu {
        padding: 0;
        border: 1px solid #ddd;
    }

    /* Styling untuk item utama */
    .main-item {
        padding: 10px 15px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }

    .main-item:hover {
        background-color: #f8f9fa;
    }

    /* Styling untuk submenu */
    .submenu {
        display: none;
        background-color: #fff;
        position: absolute;
        left: 100%;
        top: 0;
        min-width: 250px;
        border: 1px solid #ddd;
        box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
    }

    .submenu .dropdown-item {
        padding: 8px 15px;
        color: #333;
    }

    .submenu .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    /* Menampilkan submenu saat hover */
    .main-item:hover+.submenu,
    .submenu:hover {
        display: block;
    }

    /* Styling untuk arrow icon */1
    .fa-arrow-right {
        font-size: 0.8rem;
    }

    /* Tampilkan burger menu hanya di mobile */
    @media (max-width: 991.98px) {
        .navbar-toggler {
            display: block;
        }
    }

    /* Styling untuk mobile */
    @media (max-width: 991.98px) {
        .navbar-collapse {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            padding: 1rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: none;
            transition: all 0.3s ease;
        }

        .navbar-collapse.show {
            display: block;
        }

        .navbar-nav {
            flex-direction: column !important;

        }

        .nav-item {
            width: 100%;
            margin: 0.5rem 0 !important;
            text-align: center;
            /* Menambahkan ini */
        }

        /* Menambahkan ini untuk dropdown menu */
        .dropdown-menu {
            width: 100%;
            text-align: center;
            border: none;
            box-shadow: none;
            padding: 0;
        }

        .dropdown-item {
            text-align: center;
        }

        .submenu.show {
            display: block;
        }

        /* Styling untuk item utama di mobile */
        .main-item {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        /* Mengubah arah arrow di mobile */
        .fa-arrow-right {
            transform: rotate(90deg);
            transition: transform 0.3s ease;
        }

        .fa-arrow-right.rotated {
            transform: rotate(-90deg);
        }

        /* Menyesuaikan dropdown menu di mobile */
        .dropdown-menu {
            position: static !important;
            width: 100%;
            min-width: 100%;
            border: none;
            box-shadow: none;
            padding: 0;
        }

        .dropdown-item {
            padding: 8px 15px;
            text-align: left;
        }

        /* Ensure submenu appears below the main item in mobile view */
        .submenu {
            position: static;
            width: 100%;
            box-shadow: none;
            border: none;
        }

        .submenu.show {
            display: block;
        }
    }

    .product-cards {
        width: 100%;
        max-width: 800px;
        padding: 1rem;
    }

    @media (max-width: 768px) {
        .product-cards {
            width: 100%;
        }
    }

    /* Default style for desktop */
    #productCards {
        display: none;
    }

    /* Show as a card layout on desktop */
    @media (min-width: 992px) {
        #productCards {
            display: block;
        }
    }

    /* Show as a dropdown list on mobile */
    @media (max-width: 991.98px) {
        #productCards {
            display: block;
            position: static;
            width: 100%;
            padding: 0;
            box-shadow: none;
        }

        #productCards .card {
            display: none;
        }

        #productCards .dropdown-item {
            display: block;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
    }
</style>
@php
    $data = [
        [
            'name' => 'GlobalSign',
            'image' => 'https://gudangssl.id/wp-content/uploads/2019/04/global-sign.png',
            'link' => 'https://gudangssl.id/globalsign-ssl?_ga=2.222814171.1174824454.1737355957-1391583881.1736907828',
            'description' => 'SSL yang telah dikenal di seluruh dunia karena inovasi nya.',
        ],
        [
            'name' => 'AlphaSSL',
            'image' => 'https://gudangssl.id/wp-content/uploads/2019/04/1-2.png',
            'link' => 'https://gudangssl.id/alphassl',
            'description' => 'SSL level tertinggi dengan harga yang sangat murah.',
        ],
        [
            'name' => 'Entrust SSL',
            'image' => 'https://gudangssl.id/wp-content/uploads/2019/04/2-2.png',
            'link' => 'https://gudangssl.id/entrust-ssl',
            'description' => 'SSL yang lengkap dengan layanan bantuan yang cepat.',
        ],
        [
            'name' => 'Symantec SSL',
            'image' => 'https://gudangssl.id/wp-content/uploads/2019/04/symantec.png',
            'link' => 'https://gudangssl.id/symantec-ssl',
            'description' => 'SSL terpopuler di industri SSL Certificate.',
        ],
        [
            'name' => 'Sectigo SSL',
            'image' => 'https://gudangssl.id/wp-content/uploads/2019/04/sectigo.png',
            'link' => 'https://gudangssl.id/sectigo-ssl',
            'description' => 'SSL terkemuka di dunia, dengan market share sebesar 38%.',
        ],
        [
            'name' => 'RapidSSL',
            'image' => 'https://gudangssl.id/wp-content/uploads/2019/04/3-3.png',
            'link' => 'https://gudangssl.id/rapidssl',
            'description' => 'SSL dengan harga yang terjangkau dengan proses yang cepat dan mudah.',
        ],
        [
            'name' => 'Thawte SSL',
            'image' => 'https://gudangssl.id/wp-content/uploads/2019/05/sslthawtemurah.png',
            'link' => 'https://gudangssl.id/thawte-ssl',
            'description' => 'SSL pertama yang diterbitkan ke publik di luar Amerika Serikat.',
        ],
        [
            'name' => 'GeoTrust SSL',
            'image' => 'https://gudangssl.id/wp-content/uploads/2019/05/sslgeotrustmurah.png',
            'link' => 'https://gudangssl.id/geotrust',
            'description' => 'SSL terbesar dengan lebih dari 100.000 pelanggan.',
        ],
        [
            'name' => 'Certum SSL',
            'image' => 'https://gudangssl.id/wp-content/uploads/2019/04/certum.png',
            'link' => 'https://gudangssl.id/certum-ssl',
            'description' =>
                'Certum didirikan lebih dari 20 tahun yang lalu, salah satu Otoritas Sertifikat tertua di Eropa.',
        ],
    ];
@endphp

<div id="custom-navbar">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <header id="page-topbardefault">
        <div class="navbar-header bg-success text-white" style="height: 60px; background: #61C95C !important;">
            @php
                $companyLogo = Cfg::getValue('LogoURL');
                $defaultLogo = Theme::asset('assets/images/WHMCEPS.png');
            @endphp
            <div class="container d-flex justify-content-between align-items-center h-100">
                <div>
                    <span>WhatsApp: 0811-2631-588</span>
                </div>
                <div>
                    <span>Call Center: 021-39719900 | Email: info@gudangssl.id</span>
                </div>
            </div>
        </div>
        <div class="navbar-header">
            <div class="container-fluid d-flex align-items-center justify-content-between py-3">
                <a href="{{ url('/home') }}" class="navbar-brand">
                    @if (empty($companyLogo))
                        <img src="{{ $defaultLogo }}" alt="company-logo" height="50">
                    @else
                        <img src="{{ $companyLogo }}" alt="company-logo" height="50">
                    @endif
                </a>


                <div class="collapse navbar-collapse" id="navbarContent">
                    <ul class="navbar-nav flex-row align-items-center ">
                        <!-- Menu Item -->
                        <li class="nav-item mr-3">
                            <a href="{{ url('/home') }}" class="nav-link">
                                <i class="feather-home"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>

                        <li class="nav-item mr-3">
                            <a href="{{ url('/services/myservices') }}" class="nav-link">
                                <span>SSL Saya </span>
                            </a>
                        </li>

                        <!-- Billing Dropdown -->
                        <li class="nav-item dropdown mr-3">
                            <a href="#" class="nav-link " id="keuanganDropdown" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                Finance/Billing <i class="fas fa-chevron-down"></i>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="keuanganDropdown">
                                <a class="dropdown-item" href="{{ url('billinginfo/myinvoices') }}">Tagihan Saya</a>
                                <a class="dropdown-item"
                                    href="{{ route('pages.support.openticket.index', ['step' => '2', 'deptid' => '4']) }}">Pengembalian
                                    Dana</a>
                            </div>
                        </li>

                        <!-- Product Button for All Screens -->
                        <div id="productDropdown" class="nav-item dropdown mr-3">
                            <a href="#" class="nav-link productDropdownToggle" aria-haspopup="true"
                                aria-expanded="false">
                                Produk <i class="productDropdownToggle fas fa-chevron-down"></i>
                            </a>
                            <!-- Mobile Dropdown -->
                            <div id="productCardsDropdown" class="dropdown-menu d-lg-none">
                                @foreach ($data as $product)
                                    <a class="dropdown-item" href="{{ $product['link'] }}">
                                        <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}"
                                            class="img-fluid" style="width: 20px; height: 20px;">
                                        <span>{{ $product['name'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        {{-- <!-- Dropdown Menu -->
                        <li class="nav-item dropdown mr-3">
                           <a href="#" class="nav-link " id="layananDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              SSL Saya <i class="fas fa-chevron-down"></i>
                           </a>

                           <div class="dropdown-menu" aria-labelledby="layananDropdown">
                                 <a class="dropdown-item" href="{{ url('services/myservices') }}">Layanan Saya</a>
                                 <a class="dropdown-item" href="{{ route('cart') }}">Tambah Layanan Baru</a>
                           </div>
                        </li> --}}

                        <!-- Another Dropdown Menu -->
                        <li class="nav-item dropdown mr-3">
                            <a href="#" class="nav-link " id="domainDropdown" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                Jenis SSL <i class="fas fa-chevron-down"></i>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="domainDropdown">
                                <!-- Tingkat Validasi -->
                                <div class="d-flex justify-content-between align-items-center dropdown-item main-item">
                                    <span>Tingkat Validasi</span>
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                                <div class="submenu">
                                    <a class="dropdown-item" href="https://gudangssl.id/domain-validation">Domain Validation (DV) SSL</a>
                                    <a class="dropdown-item" href="https://gudangssl.id/organization-validation">Organization Validation (OV) SSL</a>
                                    <a class="dropdown-item" href="https://gudangssl.id/extended-validation">Extended Validation (EV) SSL</a>
                                </div>

                                <!-- Jumlah Domain -->
                                <div class="d-flex justify-content-between align-items-center dropdown-item main-item">
                                    <span>Jumlah Domain</span>
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                                <div class="submenu">
                                    <a class="dropdown-item" href="https://gudangssl.id/single-domain">Single Domain SSL</a>
                                    <a class="dropdown-item" href="https://gudangssl.id/wildcard">Wildcard SSL</a>
                                    <a class="dropdown-item" href="https://gudangssl.id/multi-domain">Multi Domain SSL</a>
                                </div>
                            </div>
                        </li>

                        <!-- Another Dropdown Menu -->
                        

                        <!-- Support Dropdown -->
                        <li class="nav-item dropdown mr-3">
                            <a href="#" class="nav-link" id="dukunganDropdown" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                Bantuan <i class="fas fa-chevron-down"></i>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="dukunganDropdown">
                                <a class="dropdown-item" href="https://gudangssl.id/faq">FAQ</a>
                                <div class="d-flex justify-content-between align-items-center dropdown-item main-item">
                                    <span>Support Ticket</span>
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                                <div class="submenu">
                                    <a class="dropdown-item" href="{{ url('support/openticket') }}">Buka Tiket Baru</a>
                                    <a class="dropdown-item" href="{{ url('support/mytickets') }}">Tiket Saya</a>
                                </div>
                                <div class="d-flex justify-content-between align-items-center dropdown-item main-item">
                                    <span>Panduan</span>
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                                <div class="submenu">
                                    <a class="dropdown-item" href="https://gudangssl.id/panduan-instalasi-ssl">Panduan Instalasi</a>
                                    <a class="dropdown-item" href="https://gudangssl.id/panduan-pembayaran">Panduan Pemesanan/Pembayaran</a>
                                    <a class="dropdown-item" href="https://gudangssl.id/layanan-instalasi-ssl">Layanan Instalasi</a>
                                    <a class="dropdown-item" href="https://gudangssl.id/panduan-validasi-ssl">Panduan Instalasi</a>
                                </div>
                                
                            </div>
                        </li>

                        <!-- Affiliate -->
                        <li class="nav-item dropdown mr-3">
                            <a href="#" class="nav-link" id="tentangkamiDropdown" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                Tentang Kami <i class="fas fa-chevron-down"></i>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="tentangkamiDropdown">
                                <a class="dropdown-item" href="https://gudangssl.id/mengapa-memilih-kami">Mengapa Harus GudangSSL</a>
                                <a class="dropdown-item" href="https://gudangssl.id/partner">Partner Kami</a>
                                <a class="dropdown-item" href="https://gudangssl.id/refund-policy">Refund Policy</a>
                                <a class="dropdown-item" href="https://gudangssl.id/hubungi-kami">Hubungi Kami</a>
                                <a class="dropdown-item" href="https://gudangssl.id/service-policy">Service Policy</a>
                                <a class="dropdown-item" href="https://gudangssl.id/automated-ssl-activation-ssl-policy">Automated SSL Activation Policy</a>
                                <a class="dropdown-item" href="https://gudangssl.id/referral-policy">Referral Policy</a>
                                <a class="dropdown-item" href="https://gudangssl.id/definisi-project">Definisi Project</a>
                            </div>
                        </li>

                        <!-- Dark Mode Switch -->
                        <li class="nav-item mr-3">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="darkSwitch">
                                <label class="custom-control-label sidebar-text" for="darkSwitch">
                                    <i class="feather-moon mr-2"></i>
                                    <span>Dark Mode</span>
                                </label>
                            </div>
                        </li>
                    </ul>
                </div>
                
                <div class="d-flex align-items-center ml-auto">
                    @auth
                        <div class="dropdown d-inline-block">
                            <button type="button" class="btn btn-success" id="page-header-user-dropdown"
        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    Halo, {{ ucfirst(session('user.firstname', Auth::user()->firstname)) }}
    <i class="fas fa-chevron-down"></i>
</button>
                            <div class="dropdown-menu dropdown-menu-right">
                                @php
                                    $menuItems = [
                                        ['url' => 'emailnotes', 'label' => 'Email Notes'],
                                        ['url' => 'uploadterms', 'label' => 'Upload Account Terms'],
                                        ['url' => 'detailprofile', 'label' => 'Edit Account Details'],
                                        ['url' => 'contactsub', 'label' => 'Contact / Sub-Account'],
                                        ['url' => 'securitysettings', 'label' => 'Security Settings'],
                                        ['url' => 'updatepassword', 'label' => 'Update Password'],
                                        // ['url' => route('logout'), 'label' => 'Log Out', 'class' => 'text-danger'],
                                    ];
                                @endphp

                                @foreach ($menuItems as $item)
                                    <a class="dropdown-item d-flex align-items-center justify-content-between"
                                        href="{{ url($item['url']) }}">
                                        <span class="{{ $item['class'] ?? '' }}">{{ $item['label'] }}</span>
                                    </a>
                                @endforeach

                                 <!-- Add the logout link with form submission -->
                                <a class="dropdown-item d-flex align-items-center justify-content-between"
                                href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <span class="text-danger">Log Out</span>
                                </a>
                                <form id="logout-form" action="{{ route('logoutClient') }}" method="POST" style="display: none;">
                                    @csrf
                                </form>
                            </div>
                        </div>
                    @endauth
                    <!-- Move Hamburger Menu Here -->
                    <button class="navbar-toggler ml-2 d-lg-none" type="button" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>

            </div>
        </div>
    </header>
</div>

<!-- Product Cards for Desktop -->
<div id="productCards" class="d-none mt-4 product-cards">
    <div class="card mb-4 text-center">
        <div class="card-body">
            <div class="row">
                @foreach ($data as $product)
                    <div class="col-12 col-sm-6 col-md-4 mb-4 border-bottom-2 border-2 border-secondary">
                        <a href="{{ $product['link'] }}" class="d-block text-decoration-none">
                            <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" class="img-fluid">
                            <h5 class="card-title mt-2">{{ $product['name'] }}</h5>
                            <p class="card-text text-left font-weight-normal form-control-sm text-dark">
                                {{ $product['description'] }}
                            </p>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>

<script>
    $(document).ready(function() {
        const $productDropdownToggle = $('.productDropdownToggle');
        const $productCards = $('#productCards');
        const $productCardsDropdown = $('#productCardsDropdown');

        if ($productDropdownToggle.length) {
            $productDropdownToggle.on('click', function(e) {
                e.preventDefault();
                if ($(window).width() >= 992) { // Desktop
                    $productCards.show();
                } else { // Mobile
                    $productCardsDropdown.toggleClass('show');
                }
            });

            // Close when clicking outside
            $(document).on('click', function(e) {
                if ($(window).width() >= 992) {
                    if (!$productDropdownToggle.is(e.target) && !$productCards.is(e.target) && $productCards.has(e.target).length === 0) {
                        $productCards.addClass('d-none');
                    }
                } else {
                    if (!$productDropdownToggle.is(e.target) && !$productCardsDropdown.is(e.target) && $productCardsDropdown.has(e.target).length === 0) {
                        $productCardsDropdown.removeClass('show');
                    }
                }
            });
        }

        // Mobile menu toggle using jQuery
        const $navbar = $('#custom-navbar');
        const $navbarToggler = $navbar.find('.navbar-toggler');
        const $navbarContent = $navbar.find('#navbarContent');

        let isMenuOpen = false;

        if ($navbarToggler.length && $navbarContent.length) {
            $navbarToggler.on('click', function(e) {
                e.preventDefault();
                isMenuOpen = !isMenuOpen;
                if (isMenuOpen) {
                    $navbarContent.addClass('show');
                } else {
                    $navbarContent.removeClass('show');
                }
            });

            // Close menu when clicking outside
            $(document).on('click', function(e) {
                if (isMenuOpen && !$navbarContent.is(e.target) && !$navbarContent.has(e.target).length && !$navbarToggler.is(e.target) && !$navbarToggler.has(e.target).length) {
                    $navbarContent.removeClass('show');
                    isMenuOpen = false;
                }
            });
        }

        // Handle all dropdowns including product dropdown
        const $dropdowns = $('.dropdown-toggle, #productDropdown');
        $dropdowns.each(function() {
            $(this).on('click', function(e) {
                e.preventDefault();
                const $menu = this.id === 'productDropdown' ? $productCards : $(this).next();

                // Close other dropdowns
                $dropdowns.each(function() {
                    if (this !== e.currentTarget) {
                        const $otherMenu = this.id === 'productDropdown' ? $productCards : $(this).next();
                        $otherMenu.removeClass('show').addClass('d-none');
                    }
                });

                // Toggle current dropdown
                if (this.id === 'productDropdown') {
                    $menu.toggleClass('d-none');
                } else {
                    $menu.toggleClass('show');
                }
            });
        });

        const $mainItems = $('.main-item');
        $mainItems.each(function() {
            $(this).on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const $submenu = $(this).next();
                const $arrow = $(this).find('.fa-arrow-right');

                $submenu.toggleClass('show');
                $arrow.toggleClass('rotated');
            });
        });

        // Menutup submenu saat mengklik di luar
        $(document).on('click', function(e) {
            if ($(window).width() <= 991.98) {
                if (!$(e.target).closest('.main-item').length && !$(e.target).closest('.submenu').length) {
                    $('.submenu').removeClass('show');
                    $('.fa-arrow-right').removeClass('rotated');
                }
            }
        });
    });
</script>
