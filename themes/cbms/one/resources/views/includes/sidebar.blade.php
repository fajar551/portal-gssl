 <div class="vertical-menu">
    <div data-simplebar class="h-100">
       @php
          $companyLogo = Cfg::getValue('LogoURL');
          $defaultLogo = Theme::asset('assets/images/WHMCEPS.png');
       @endphp
       <!-- LOGO -->
       <div class="navbar-brand-box">
          <a href="{{ url('home') }}" class="logo">
             <span>
                @if (empty($companyLogo))
                   <img src="{{ $defaultLogo }}" alt="company-logo" width="100">
                @else
                   <img src="{{ $companyLogo }}" alt="company-logo" width="100">
                @endif
             </span>
             <i>
                @if (empty($companyLogo))
                   <img src="{{ $defaultLogo }}" alt="company-logo" width="100">
                @else
                   <img src="{{ $companyLogo }}" alt="company-logo" width="100">
                @endif
             </i>
          </a>
       </div>

       <!--- Sidemenu -->
       <div id="sidebar-menu">
          <!-- Left Menu Start -->
          <ul class="metismenu list-unstyled" id="side-menu">
             <li class="menu-title">Menu Fajar</li>

             <li>
                <a href="{{ url('/home') }}"><i
                      class="feather-home"></i><span>{{ __('client.clientareanavdashboard') }}</span></a>
             </li>
             <li>
                <a href="javascript: void(0);" class="has-arrow"><i
                      class="feather-server"></i><span>{{ __('client.navservices') }}</span></a>
                <ul class="sub-menu" aria-expanded="false">
                   <li><a href="{{ url('services/myservices') }}">{{ __('client.clientareanavservices') }}</a>
                   </li>
                   {{-- <li><a href="{{ url('services/cancelservice') }}">{{ __('client.batalhosting') }}</a></li> --}}
                   <li><a href="{{ route('cart') }}">{{ __('client.choosenewservices') }}</a></li>
                </ul>
             </li>
             <li>
               <a href="javascript: void(0);" class="has-arrow">
                  <i class="fa fa-globe-asia"></i><span> Domain</span></a>
               <ul class="sub-menu" aria-expanded="false">
                  <li>
                     <a href="{{ url('domain/mydomains') }}">Domain Saya</a>
                  </li>
                  <li>
                     <a href="{{ url('') }}">Perpanjangan Domain</a>
                  </li>
                  <li>
                     <a href="{{ url('') }}">Daftar Domain Baru</a>
                  </li>
                  <li>
                     <a href="{{ url('domain/transferdomains') }}">Transfer Domain</a>
                  </li>
                  <li>
                     <a href="{{ url('') }}">Unggah Persyaratan Domain</a>
                  </li>
                  <li>
                     <a href="{{ url('domain/lelangdomains') }}">Lelang Domain</a>
                  </li>
                  <li>
                     <a href="{{ url('domain/backorders') }}">Backorder Domain</a>
                  </li>
                  <li>
                     <a href="{{ url('domain/selldomains') }}">Sell/Rent Domain</a>
                  </li>
                  <li>
                     <a href="{{ url('') }}">Backlink Domain</a>
                  </li>
               </ul>
            </li>

             <!--<li>-->
             <!--   <a href="javascript: void(0);" class="has-arrow"><i-->
             <!--         class="feather-globe"></i><span>{{ __('client.navdomains') }}</span></a>-->
             <!--   <ul class="sub-menu" aria-expanded="false">-->
             <!--      <li><a href="{{ url('domains/mydomains') }}">{{ __('client.clientareanavdomains') }}</a>-->
             <!--      </li>-->
             <!--      {{-- <li><a href="{{ url('domains/transferdomain') }}">{{ __('client.transferdomain') }}</a>-->
             <!--      </li> --}}-->
             <!--   </ul>-->
             <!--</li>-->
             <li>
                <a href="javascript: void(0);" class="has-arrow"><i
                      class="feather-credit-card"></i><span>{{ __('client.navbilling') }}</span></a>
                <ul class="sub-menu" aria-expanded="false">
                   <li><a href="{{ url('billinginfo/myinvoices') }}">{{ __('client.invoices') }}</a></li>
                   {{-- <li><a href="{{ url('billinginfo/manualrequest') }}">Manual Billing Requests</a></li> --}}
                   <li><a href="{{ route('pages.support.openticket.index', ['step' => '2', 'deptid' => '6']) }}">{{ __('client.faktur') }}</a></li>
                   {{-- <li><a href="{{ url('billinginfo/refund') }}">{{ __('client.refund') }}</a></li> --}}
                   {{-- <li><a href="{{ url('billinginfo/offerforme') }}">{{ __('client.offer') }}</a></li> --}}
                </ul>
             </li>
             <li>
                <a href="javascript: void(0);" class="has-arrow"><i
                      class="feather-help-circle"></i><span>{{ __('client.navsupport') }}</span></a>
                <ul class="sub-menu" aria-expanded="false">
                   <li><a href="{{ url('support/openticket') }}">{{ __('client.opennewticket') }}</a>
                   </li>
                   <li><a href="{{ url('support/mytickets') }}">{{ __('client.navtickets') }}</a></li>
                   {{-- <li><a href="{{ url('support/networkstatus') }}">{{ __('client.networkstatustitle') }}</a>
                   </li> --}}
                </ul>
             </li>
             <li>
                <a href="{{ url('affiliate') }}"><i class="far fa-handshake"></i><span>Affiliate</span></a>
             </li>
             <li id="test-nav">


             </li>
             <li class="fix-bottom px-5 py-2">
                {{-- <a href="#" id="darkSwitch"><i class="feather-moon"></i><span>Dark Mode</span></a> --}}
                <div class="custom-control custom-switch">
                   <input type="checkbox" class="custom-control-input" id="darkSwitch">
                   <label class="custom-control-label sidebar-text" for="darkSwitch"><i
                         class="feather-moon mr-2"></i><span>Dark Mode</span></label>
                </div>
             </li>

          </ul>
       </div>
       <!-- Sidebar -->
    </div>
 </div>
