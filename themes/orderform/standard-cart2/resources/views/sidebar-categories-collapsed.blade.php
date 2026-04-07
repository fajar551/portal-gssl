@if (isset($productgroups))
    <div class="categories-collapsed visible-xs visible-sm clearfix mb-4">

        <div class="pull-left form-inline">
            <form method="get" action="">
                <select name="gid" onchange="submit()" class="form-control">
                    <optgroup label="Product Categories">
                        @foreach ($productgroups as $productgroup)
                            <option value="{{$productgroup['gid']}}"
                                @if ($gid == $productgroup['gid'])
                                    selected="selected"
                                @endif>
                                {{$productgroup['name']}}
                            </option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Actions">
                        @auth('web')
                            <option value="addons"{{$gid == "addons" ? ' selected' : ''}}>{{Lang::get('client.cartproductaddons')}}</option>
                            @if ($renewalsenabled)
                                <option value="renewals"{{$gid == "renewals" ? ' selected':''}}>{{Lang::get('client.domainrenewals')}}</option>
                            @endif
                        @endauth
                        @if ($registerdomainenabled)
                            <option value="registerdomain"{{$domain == "register" ? ' selected':''}}>{{Lang::get('client.navregisterdomain')}}</option>
                        @endif
                        @if ($transferdomainenabled)
                            <option value="transferdomain"{{$domain == "transfer" ? ' selected':''}}>{{Lang::get('client.transferinadomain')}}</option>
                        @endif
                        {{-- <option value="viewcart"{{$action == "view" ? ' selected':''}}>{{Lang::get('client.viewcart')}}</option> --}}
                        <option value="viewcart" {{($action ?? '') == "view" ? ' selected' : ''}}>{{Lang::get('client.viewcart')}}</option>

                    </optgroup>
                </select>
            </form>
        </div>

        @if (!Auth::guard('web')->check() && (isset($currencies) && $currencies))
            <div class="pull-right form-inline">
                <form 
                    method="post" 
                    {{-- Ganti bagian ini --}}
        @if ($action)
        action="{{"?a={$action}"}}"
    @elseif ($gid)
        action="{{"?gid={$gid}"}}"
    @endif
    {{-- Dengan yang baru --}}
    @if (isset($action) && $action)
        action="{{"?a={$action}"}}"
    @elseif (isset($gid) && $gid)
        action="{{"?gid={$gid}"}}"
    @endif
                >
                <select name="currency" onchange="submit()" class="form-control">
                  <option value="">{{Lang::get('client.choosecurrency')}}</option>
                  @foreach ($currencies as $listcurr)
                      <option value="{{$listcurr['id']}}"{{$listcurr['id'] == $currency['id'] ? ' selected':''}}>{{$listcurr['code']}}</option>
                  @endforeach
              </select>
                </form>
            </div>
        @endif
        
    </div>
@endif
