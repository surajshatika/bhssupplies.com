@extends('backend.layouts.app')
<style>
    .radio-button input[type="radio"] {
        display: none;
    }

    .radio-button label {
        display: inline-block;
        background-color: #d1d1d1;
        /*padding: 4px 11px;*/
        font-size: 15px;
        cursor: pointer;
        border-radius: 1.2rem;
        padding: 0.6rem 1.2rem;
    }

    .radio-button input[type="radio"]:checked + label {
        background-color: #76cf9f;
    }

    #frm_cht .ch_tables {
    }

    #frm_cht .ch_tables label {
    }


    #table_selection {
        display: none;
    }

    .currently-loading {
        opacity: 0.75;
        -moz-opacity: 0.75;
        filter: alpha(opacity=75);
        background-image: url({{ static_asset('backup_restore_loading.gif') }});
        background-repeat: no-repeat;
        position: absolute;
        height: 100%;
        width: 100%;
        z-index: 10;
        /*left: 50%;*/
        /*top: 50%;*/
        /*display:block;*/
        background-size: contain;
        /*background-size: cover;*/

    }

    /*.currently-loading img {*/
    /*    height: 100%;*/
    /*    object-fit: contain;*/
    /*}*/








    /*.cssload-cssload-ballsncups {*/
    /*    !*width: 244px;*!*/
    /*    width: 390px;*/
    /*    height: 49px;*/
    /*    position: absolute;*/
    /*    left: 50%;*/
    /*    margin: 24px -122px;*/
    /*    list-style-type: none;*/
    /*}*/

    /*.cssload-cssload-ballsncups li {*/
    /*    float: left;*/
    /*    position: relative;*/
    /*}*/

    /*.cssload-circle {*/
    /*    width: 39px;*/
    /*    height: 39px;*/
    /*    border-radius: 0 0 50% 50%;*/
    /*    border: 4px solid rgb(0,0,0);*/
    /*    border-top: 0;*/
    /*    border-left: 0;*/
    /*    border-right: 0;*/
    /*}*/

    /*.cssload-ball {*/
    /*    position: absolute;*/
    /*    content: "";*/
    /*    width: 19px;*/
    /*    height: 19px;*/
    /*    top: 50%;*/
    /*    left: 50%;*/
    /*    margin-top: -10px;*/
    /*    margin-left: -10px;*/
    /*    border-radius: 100%;*/
    /*    background: rgb(0,0,0);*/
    /*    box-shadow: 0 -15px 0 0 rgba(0,0,0,0.15), 0 -10px 0 0 rgba(0,0,0,0.1), 0 -5px 0 0 rgba(0,0,0,0.05);*/
    /*    -o-box-shadow: 0 -15px 0 0 rgba(0,0,0,0.15), 0 -10px 0 0 rgba(0,0,0,0.1), 0 -5px 0 0 rgba(0,0,0,0.05);*/
    /*    -ms-box-shadow: 0 -15px 0 0 rgba(0,0,0,0.15), 0 -10px 0 0 rgba(0,0,0,0.1), 0 -5px 0 0 rgba(0,0,0,0.05);*/
    /*    -webkit-box-shadow: 0 -15px 0 0 rgba(0,0,0,0.15), 0 -10px 0 0 rgba(0,0,0,0.1), 0 -5px 0 0 rgba(0,0,0,0.05);*/
    /*    -moz-box-shadow: 0 -15px 0 0 rgba(0,0,0,0.15), 0 -10px 0 0 rgba(0,0,0,0.1), 0 -5px 0 0 rgba(0,0,0,0.05);*/
    /*}*/

    /*.cssload-circle {*/
    /*    animation: rotate 1.73s cubic-bezier(0.45, 0, 1, 1) infinite;*/
    /*    -o-animation: rotate 1.73s cubic-bezier(0.45, 0, 1, 1) infinite;*/
    /*    -ms-animation: rotate 1.73s cubic-bezier(0.45, 0, 1, 1) infinite;*/
    /*    -webkit-animation: rotate 1.73s cubic-bezier(0.45, 0, 1, 1) infinite;*/
    /*    -moz-animation: rotate 1.73s cubic-bezier(0.45, 0, 1, 1) infinite;*/
    /*}*/

    /*.cssload-ball {*/
    /*    animation: fall 1.73s cubic-bezier(0.95, 0, 1, 1) infinite;*/
    /*    -o-animation: fall 1.73s cubic-bezier(0.95, 0, 1, 1) infinite;*/
    /*    -ms-animation: fall 1.73s cubic-bezier(0.95, 0, 1, 1) infinite;*/
    /*    -webkit-animation: fall 1.73s cubic-bezier(0.95, 0, 1, 1) infinite;*/
    /*    -moz-animation: fall 1.73s cubic-bezier(0.95, 0, 1, 1) infinite;*/
    /*}*/

    /*.cssload-cssload-ballsncups li:nth-child(1) div {*/
    /*    animation-delay: 115ms;*/
    /*    -o-animation-delay: 115ms;*/
    /*    -ms-animation-delay: 115ms;*/
    /*    -webkit-animation-delay: 115ms;*/
    /*    -moz-animation-delay: 115ms;*/
    /*}*/

    /*.cssload-cssload-ballsncups li:nth-child(2) div {*/
    /*    animation-delay: 230ms;*/
    /*    -o-animation-delay: 230ms;*/
    /*    -ms-animation-delay: 230ms;*/
    /*    -webkit-animation-delay: 230ms;*/
    /*    -moz-animation-delay: 230ms;*/
    /*}*/

    /*.cssload-cssload-ballsncups li:nth-child(3) div {*/
    /*    animation-delay: 345ms;*/
    /*    -o-animation-delay: 345ms;*/
    /*    -ms-animation-delay: 345ms;*/
    /*    -webkit-animation-delay: 345ms;*/
    /*    -moz-animation-delay: 345ms;*/
    /*}*/

    /*.cssload-cssload-ballsncups li:nth-child(4) div {*/
    /*    animation-delay: 460ms;*/
    /*    -o-animation-delay: 460ms;*/
    /*    -ms-animation-delay: 460ms;*/
    /*    -webkit-animation-delay: 460ms;*/
    /*    -moz-animation-delay: 460ms;*/
    /*}*/

    /*.cssload-cssload-ballsncups li:nth-child(5) div {*/
    /*    animation-delay: 575ms;*/
    /*    -o-animation-delay: 575ms;*/
    /*    -ms-animation-delay: 575ms;*/
    /*    -webkit-animation-delay: 575ms;*/
    /*    -moz-animation-delay: 575ms;*/
    /*}*/

    /*.cssload-cssload-ballsncups li:nth-child(6) div {*/
    /*    animation-delay: 690ms;*/
    /*    -o-animation-delay: 690ms;*/
    /*    -ms-animation-delay: 690ms;*/
    /*    -webkit-animation-delay: 690ms;*/
    /*    -moz-animation-delay: 690ms;*/
    /*}*/

    /*.cssload-cssload-ballsncups li:nth-child(7) div {*/
    /*    animation-delay: 805ms;*/
    /*    -o-animation-delay: 805ms;*/
    /*    -ms-animation-delay: 805ms;*/
    /*    -webkit-animation-delay: 805ms;*/
    /*    -moz-animation-delay: 805ms;*/
    /*}*/

    /*.cssload-cssload-ballsncups li:nth-child(8) div {*/
    /*    animation-delay: 920ms;*/
    /*    -o-animation-delay: 920ms;*/
    /*    -ms-animation-delay: 920ms;*/
    /*    -webkit-animation-delay: 920ms;*/
    /*    -moz-animation-delay: 920ms;*/
    /*}*/




    /*@keyframes rotate {*/
    /*    0%, 20% {*/
    /*        transform: rotate(0deg);*/
    /*    }*/
    /*    100% {*/
    /*        transform: rotate(360deg);*/
    /*    }*/
    /*}*/

    /*@-o-keyframes rotate {*/
    /*    0%, 20% {*/
    /*        -o-transform: rotate(0deg);*/
    /*    }*/
    /*    100% {*/
    /*        -o-transform: rotate(360deg);*/
    /*    }*/
    /*}*/

    /*@-ms-keyframes rotate {*/
    /*    0%, 20% {*/
    /*        -ms-transform: rotate(0deg);*/
    /*    }*/
    /*    100% {*/
    /*        -ms-transform: rotate(360deg);*/
    /*    }*/
    /*}*/

    /*@-webkit-keyframes rotate {*/
    /*    0%, 20% {*/
    /*        -webkit-transform: rotate(0deg);*/
    /*    }*/
    /*    100% {*/
    /*        -webkit-transform: rotate(360deg);*/
    /*    }*/
    /*}*/

    /*@-moz-keyframes rotate {*/
    /*    0%, 20% {*/
    /*        -moz-transform: rotate(0deg);*/
    /*    }*/
    /*    100% {*/
    /*        -moz-transform: rotate(360deg);*/
    /*    }*/
    /*}*/

    /*@keyframes fall {*/
    /*    0%, 20% {*/
    /*        transform: translatey(-29px);*/
    /*        opacity: 0;*/
    /*    }*/
    /*    20%,*/
    /*    24%,*/
    /*    27%,*/
    /*    30%,*/
    /*    50%,*/
    /*    75% {*/
    /*        transform: translatey(0px);*/
    /*        opacity: 1;*/
    /*    }*/
    /*    22% {*/
    /*        transform: translatey(-10px)*/
    /*    }*/
    /*    25% {*/
    /*        transform: translatey(-6px)*/
    /*    }*/
    /*    28% {*/
    /*        transform: translatey(-2px)*/
    /*    }*/
    /*    30% {*/
    /*        box-shadow: 0px 0px 0px rgba(0,0,0,0.3);*/
    /*    }*/
    /*    75%,*/
    /*    100% {*/
    /*        box-shadow: 0 -15px 0 0 rgba(0,0,0,0.15), 0 -10px 0 0 rgba(0,0,0,0.1), 0 -5px 0 0 rgba(0,0,0,0.05);*/
    /*        transform: translatey(29px);*/
    /*        opacity: 0;*/
    /*    }*/
    /*}*/

    /*@-o-keyframes fall {*/
    /*    0%, 20% {*/
    /*        -o-transform: translatey(-29px);*/
    /*        opacity: 0;*/
    /*    }*/
    /*    20%,*/
    /*    24%,*/
    /*    27%,*/
    /*    30%,*/
    /*    50%,*/
    /*    75% {*/
    /*        -o-transform: translatey(0px);*/
    /*        opacity: 1;*/
    /*    }*/
    /*    22% {*/
    /*        -o-transform: translatey(-10px)*/
    /*    }*/
    /*    25% {*/
    /*        -o-transform: translatey(-6px)*/
    /*    }*/
    /*    28% {*/
    /*        -o-transform: translatey(-2px)*/
    /*    }*/
    /*    30% {*/
    /*        box-shadow: 0px 0px 0px rgba(0,0,0,0.3);*/
    /*    }*/
    /*    75%,*/
    /*    100% {*/
    /*        -o-box-shadow: 0 -15px 0 0 rgba(0,0,0,0.15), 0 -10px 0 0 rgba(0,0,0,0.1), 0 -5px 0 0 rgba(0,0,0,0.05);*/
    /*        -o-transform: translatey(29px);*/
    /*        opacity: 0;*/
    /*    }*/
    /*}*/

    /*@-ms-keyframes fall {*/
    /*    0%, 20% {*/
    /*        -ms-transform: translatey(-29px);*/
    /*        opacity: 0;*/
    /*    }*/
    /*    20%,*/
    /*    24%,*/
    /*    27%,*/
    /*    30%,*/
    /*    50%,*/
    /*    75% {*/
    /*        -ms-transform: translatey(0px);*/
    /*        opacity: 1;*/
    /*    }*/
    /*    22% {*/
    /*        -ms-transform: translatey(-10px)*/
    /*    }*/
    /*    25% {*/
    /*        -ms-transform: translatey(-6px)*/
    /*    }*/
    /*    28% {*/
    /*        -ms-transform: translatey(-2px)*/
    /*    }*/
    /*    30% {*/
    /*        box-shadow: 0px 0px 0px rgba(0,0,0,0.3);*/
    /*    }*/
    /*    75%,*/
    /*    100% {*/
    /*        -ms-box-shadow: 0 -15px 0 0 rgba(0,0,0,0.15), 0 -10px 0 0 rgba(0,0,0,0.1), 0 -5px 0 0 rgba(0,0,0,0.05);*/
    /*        -ms-transform: translatey(29px);*/
    /*        opacity: 0;*/
    /*    }*/
    /*}*/

    /*@-webkit-keyframes fall {*/
    /*    0%, 20% {*/
    /*        -webkit-transform: translatey(-29px);*/
    /*        opacity: 0;*/
    /*    }*/
    /*    20%,*/
    /*    24%,*/
    /*    27%,*/
    /*    30%,*/
    /*    50%,*/
    /*    75% {*/
    /*        -webkit-transform: translatey(0px);*/
    /*        opacity: 1;*/
    /*    }*/
    /*    22% {*/
    /*        -webkit-transform: translatey(-10px)*/
    /*    }*/
    /*    25% {*/
    /*        -webkit-transform: translatey(-6px)*/
    /*    }*/
    /*    28% {*/
    /*        -webkit-transform: translatey(-2px)*/
    /*    }*/
    /*    30% {*/
    /*        box-shadow: 0px 0px 0px rgba(0,0,0,0.3);*/
    /*    }*/
    /*    75%,*/
    /*    100% {*/
    /*        -webkit-box-shadow: 0 -15px 0 0 rgba(0,0,0,0.15), 0 -10px 0 0 rgba(0,0,0,0.1), 0 -5px 0 0 rgba(0,0,0,0.05);*/
    /*        -webkit-transform: translatey(29px);*/
    /*        opacity: 0;*/
    /*    }*/
    /*}*/

    /*@-moz-keyframes fall {*/
    /*    0%, 20% {*/
    /*        -moz-transform: translatey(-29px);*/
    /*        opacity: 0;*/
    /*    }*/
    /*    20%,*/
    /*    24%,*/
    /*    27%,*/
    /*    30%,*/
    /*    50%,*/
    /*    75% {*/
    /*        -moz-transform: translatey(0px);*/
    /*        opacity: 1;*/
    /*    }*/
    /*    22% {*/
    /*        -moz-transform: translatey(-10px)*/
    /*    }*/
    /*    25% {*/
    /*        -moz-transform: translatey(-6px)*/
    /*    }*/
    /*    28% {*/
    /*        -moz-transform: translatey(-2px)*/
    /*    }*/
    /*    30% {*/
    /*        box-shadow: 0px 0px 0px rgba(0,0,0,0.3);*/
    /*    }*/
    /*    75%,*/
    /*    100% {*/
    /*        -moz-box-shadow: 0 -15px 0 0 rgba(0,0,0,0.15), 0 -10px 0 0 rgba(0,0,0,0.1), 0 -5px 0 0 rgba(0,0,0,0.05);*/
    /*        -moz-transform: translatey(29px);*/
    /*        opacity: 0;*/
    /*    }*/
    /*}*/

















</style>

@section('content')
    {{--    <div class="aiz-titlebar text-left mt-2 mb-3">--}}
    {{--        <div class="row align-items-center">--}}
    {{--            <div class="col">--}}
    {{--                <h1 class="h3">{{translate('Backups And Restore (App DataBase And Folder:public/uploads)')}}</h1>--}}
    {{--            </div>--}}

    {{--        </div>--}}
    {{--    </div>--}}


    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col">
                <div class="alert alert-info" role="alert">
                    <b style="font-size:17px">{{ translate('The backup and restore (DataBase, Folder:public/uploads, Addons And WebSite) is made for you with love by Kia from Ariaclub.com') }}&#128151</b>
                </div>

            </div>
        </div>
    </div>


    <div class="card">
        @php
            $nr = count($tables);
            $cr = $nr / 5;
            $nr %$cr > 0 ? $cr++ :$cr;
            $db = "Tables_in_".env('DB_DATABASE');
            $i=0;
            $msg_select_all="Select All (%s tables)";

        @endphp

        <div class="card-header">

            <label for="checkboxkia"
                   id="advcheck">{{ translate('Advanced selection for Tables to explode from Backup') }}
                <input onchange="show_hide_selection(this)" type="checkbox" name="checkboxkia"
                       style="margin-left:-10px; margin-right:-10px; text-align:left;height:18px;">
            </label>
        </div>

        {{--    //table selection section--}}
        <div class="card-body" id="table_selection">


            <div class="card-header">
                <h6 class="fw-600 mb-0">{{translate('Select the Tables to explode from Backup')}}</h6>

                <label id="ch_all">
                    <input onchange="update_all_status({{$nr}})" type="checkbox"
                           style="margin: 0; padding: 0;  height:18px; overflow:hidden; float:left; ">{{ sprintf($msg_select_all, $nr) }}
                </label>

                <div class="card-header row gutters-5">
                    <div class="col">
                        <h5 class="mb-md-0 h6"></h5>
                    </div>
                </div>
                <div class="card-header row gutters-5">
                    <div class="col">
                        <h5 class="mb-md-0 h6"></h5>
                    </div>
                </div>
                <div class="card-header row gutters-5">
                    <div class="col">
                        <h5 class="mb-md-0 h6"></h5>
                    </div>
                </div>
                <div class="card-header row gutters-5">
                    <div class="col">
                        <h5 class="mb-md-0 h6"></h5>
                    </div>
                </div>

            </div>


            <div class="row gutters-10">
                <div class="col-lg-16">
                    <div class="card shadow-none bg-light">
                        <div class="card-body">
                            <form id="frm_cht">
                                @csrf
                                <div class="ch_tables row gutters-5">
                                    @foreach($tables as $table)
                                        @if($i >0 && ($i %$cr) == 0)
                                </div>
                                <div class="ch_tables row gutters-5">
                                    @endif
                                    <div class="col-4">
                                        <label
                                            style="margin-left:-40px; margin-right:5px; text-align:left;padding: 1px;">
                                            <input onchange="update_status(this,{{$nr}})" type="checkbox"
                                                   style="margin-left:-10px; margin-right:-10px; text-align:left;height:18px;"
                                                   name="tables[]"
                                                   value="{{ $table->$db }}">{{ $table->$db }}
                                        </label>
                                    </div>
                                    @php
                                        $i++;
                                    @endphp
                                    @endforeach
                                </div>
                            </form>

                            <div id="persian"></div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{--    //title section--}}
        {{--        <div class="card-header row gutters-5">--}}
        {{--            <div class="alert alert-info" role="alert">--}}
        {{--                <b style="font-size:20px">{{ translate('The backup and restore addon is made for you with love by Kia from Ariaclub.com') }}&#128151</b>--}}
        {{--            </div>--}}
        {{--        </div>--}}




        {{--    //(Create Backup) and (Check Permission) and (Ftp Settings) and (Create Addons Backup) buttons section--}}
        <div class="card-header row gutters-5">
            <div class="col">
                <button data-toggle="modal" data-target="#addBackup"
                        style="margin-left:1px; margin-right:10px;"
                        class="btn btn-info">{{ translate('Create Backup') }}
                    <i class="las la-archive la-2x"></i>
                </button>
                <button data-toggle="modal" data-target="#checkPermission"
                        style="margin-left:10px; margin-right:10px;"
                        class="btn btn-info">{{ translate('Check Permission') }}
                    <i class="las la-check la-2x"></i>
                </button>
                @if(1>2)
                    <button data-toggle="modal" data-target="#ftpsettings"
                            style="margin-left:10px; margin-right:10px;"
                            class="btn btn-info">{{ translate('Ftp Settings') }}</button>
                @endif

                @php
                    $addonsdir = public_path('addons');
                    //check any zip file exist in addons directory, if yes continue else show to user not any zip file exist
                    $seq = 0;
                    foreach (glob($addonsdir."/*.zip") as $filename) {
                        if (is_file($filename)) {
                            $seq = $seq + 1;
                        }
                    }
                @endphp

                @if($seq>0)
                    <button data-toggle="modal" data-target="#addBackupAddons"
                            style="margin-left:1px; margin-right:10px;"
                            class="btn btn-info">{{ translate('Create Backup From Installed Addons') }} {{$seq}} {{ translate('addons') }}
                        <i class="las la-award la-2x"></i>
                    </button>
                @else
{{--                    <button data-toggle="modal" data-target="#addBackupAddons"--}}
                    <button
                            style="margin-left:1px; margin-right:10px;"
                            class="disabled btn btn-info">{{ translate('Create Backup From Installed Addons') }} {{$seq}} {{ translate('addons') }}
                        <i class="las la-award la-2x"></i>
                    </button>
                @endif

            </div>
        </div>

        {{--    //show table of backuped content section--}}
        <form id="sort_backups" action="">



            {{--            //old method for sort date in table--}}
            {{--            <div class="col-md-3 ml-auto mr-0">--}}
            {{--                <select class="form-control form-control-xs aiz-selectpicker" name="sort" onchange="sort_backups()">--}}
            {{--                    <option value="newest"--}}
            {{--                            @if($sort_by == 'newest') selected="" @endif>{{ translate('Sort by newest') }}</option>--}}
            {{--                    <option value="oldest"--}}
            {{--                            @if($sort_by == 'oldest') selected="" @endif>{{ translate('Sort by oldest') }}</option>--}}
            {{--                </select>--}}
            {{--            </div>--}}

            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">{{ translate('Name') }}</th>
                        <th scope="col">{{ translate('Size') }}</th>
                        {{--            //old method for sort date in table--}}
                        {{--                        <th scope="col">{{ translate('Date') }}</th>--}}

                        {{--                        <th scope="col"><a href="{{route('backups')}}?sort={{ $sort_by == 'newest' ? 'oldest' : 'newest' }}"><i class="las la-sort text-blue"></i></a> {{ translate('Date') }}</th>--}}
                        <th scope="col"><a href="{{route('backups')}}?sort={{ $sort_by == 'newest' ? 'oldest' : 'newest' }}"><i class="las la-sort" style="color:#00bbf2;font-size: 18px;"></i></a> {{ translate('Date') }}</th>
                        <th scope="col">{{ translate('Backup Type') }}</th>
                        <th scope="col">{{ translate('Actions') }}</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php
                    $rownumber=0;
                    $rownumber = ($pageNum > 1) ? ($pageNum * $rowsPerPage) - $rowsPerPage : 0;
                    ?>
                    @foreach ($backups as $key => $backup)
                            <?php
                            $rownumber++;
                            ?>
                        <tr>
                            <td class="text-dark">{{ $rownumber }}</td>
                            <td class="text-dark">{{ $backup['name'] }}</td>
                            <td data-title="{{translate('backup size') }} :">
                                {{ $backup['size'] }}
                            </td>
                            <td data-title="{{ translate('backup created at') }} :">{{ $backup['date'] }}</td>
                            <td data-title="{{ translate('backup type') }} :">

                                @switch($backup['type'])
                                    @case(0)
                                        {{translate('DataBase & Storage')}}
                                        @break

                                    @case(1)
                                        {{translate('DataBase')}}
                                        @break

                                    @case(2)
                                        {{translate('Storage')}}
                                        @break

                                    @case(3)
                                        {{translate('DataBase & WebSite')}}
                                        @break

                                    @case(4)
                                        {{translate('WebSite')}}
                                        @break

                                    @case(5)
                                        {{translate('Addons')}}
                                        @break

                                    @default
                                        {{translate('Unknown')}}
                                @endswitch

                            </td>
                            <td>
                                @switch($backup['type'])
                                    @case(0)
                                        <a href="{{ route('backups.download.database', $key) }}"
                                           style="margin-left:10px; margin-right:10px; font-size: .75rem;font-weight: bold;"
                                           class="btn btn-primary">{{ translate('Download
                                Database') }}</a>

                                        {{--                                        <a href="{{ route('backups.download.storage', $key, $backup['type']) }}"--}}
                                        <a href="{{ route('backups.download.storage', ['key'=>$key,'backuptype'=>$backup['type']]) }}"
                                           style="margin-left:10px; margin-right:10px; font-size: .75rem;font-weight: bold;"
                                           class="btn btn-success">{{ translate('Download
                                storage') }}</a>
                                        @break

                                    @case(1)
                                        <a href="{{ route('backups.download.database', $key) }}"
                                           style="margin-left:10px; margin-right:10px; font-size: .75rem;font-weight: bold;"
                                           class="btn btn-primary">{{ translate('Download
                                Database') }}</a>
                                        {{--                                        <a href="{{ route('backups.download.storage', $key, $backup['type']) }}"--}}
                                        <a href="{{ route('backups.download.storage', ['key'=>$key,'backuptype'=>$backup['type']]) }}"
                                           style="margin-left:10px; margin-right:10px; font-size: .75rem;font-weight: bold;"
                                           class="disabled btn btn-success">{{ translate('Download
                                storage') }}</a>
                                        @break

                                    @case(2)
                                        <a href="{{ route('backups.download.database', $key) }}"
                                           style="margin-left:10px; margin-right:10px; font-size: .75rem;font-weight: bold;"
                                           class="disabled btn btn-primary">{{ translate('Download
                                Database') }}</a>
                                        {{--                                        <a href="{{ route('backups.download.storage', $key, $backup['type']) }}"--}}
                                        <a href="{{ route('backups.download.storage', ['key'=>$key,'backuptype'=>$backup['type']]) }}"
                                           style="margin-left:10px; margin-right:10px; font-size: .75rem;font-weight: bold;"
                                           class="btn btn-success">{{ translate('Download
                                storage') }}</a>
                                        @break

                                    @case(3)
                                        <a href="{{ route('backups.download.database', $key) }}"
                                           style="margin-left:10px; margin-right:10px; font-size: .75rem;font-weight: bold;"
                                           class="btn btn-primary">{{ translate('Download
                                Database') }}</a>
                                        {{--                                        <a href="{{ route('backups.download.storage', $key, $backup['type']) }}"--}}
                                        <a href="{{ route('backups.download.storage', ['key'=>$key,'backuptype'=>$backup['type']]) }}"
                                           style="margin-left:10px; margin-right:10px; font-size: .75rem;font-weight: bold;"
                                           class="btn btn-success">{{ translate('Download
                                website') }}</a>
                                        @break

                                    @case(4)
                                        <a href="{{ route('backups.download.database', $key) }}"
                                           style="margin-left:10px; margin-right:10px; font-size: .75rem;font-weight: bold;"
                                           class="disabled btn btn-primary">{{ translate('Download
                                Database') }}</a>
                                        {{--                                        <a href="{{ route('backups.download.storage', $key, $backup['type']) }}"--}}
                                        <a href="{{ route('backups.download.storage', ['key'=>$key,'backuptype'=>$backup['type']]) }}"
                                           style="margin-left:10px; margin-right:10px; font-size: .75rem;font-weight: bold;"
                                           class="btn btn-success">{{ translate('Download
                                website') }}</a>
                                        @break

                                    @case(5)
                                        <a href="{{ route('backups.download.database', $key) }}"
                                           style="margin-left:10px; margin-right:10px; font-size: .75rem;font-weight: bold;"
                                           class="disabled btn btn-primary">{{ translate('Download
                                Database') }}</a>
                                        {{--                                        <a href="{{ route('backups.download.storage', $key, $backup['type']) }}"--}}
                                        <a href="{{ route('backups.download.storage', ['key'=>$key,'backuptype'=>$backup['type']]) }}"
                                           style="margin-left:10px; margin-right:10px; font-size: .75rem;font-weight: bold;"
                                           class="btn btn-success">{{ translate('Download
                                addons') }}</a>
                                        @break

                                    @default
                                        <a href="{{ route('backups.download.database', $key) }}"
                                           style="margin-left:10px; margin-right:10px; font-size: .75rem;font-weight: bold;"
                                           class="btn btn-primary">{{ translate('Download
                                Database') }}</a>
                                        {{--                                        <a href="{{ route('backups.download.storage', $key, $backup['type']) }}"--}}
                                        <a href="{{ route('backups.download.storage', ['key'=>$key,'backuptype'=>$backup['type']]) }}"
                                           style="margin-left:10px; margin-right:10px; font-size: .75rem;font-weight: bold;"
                                           class="btn btn-success">{{ translate('Download
                                storage') }}</a>
                                @endswitch

                                @if ($backup['type'] <5)
                                        {{--                                        <a id="myButton" href="#!" data-toggle="modal" data-target="#restoreBackup_{{ $key }}"  onclick="show_animation({{  $backup->klid }})"--}}
{{--                                        <a id="myButton" href="#!" data-toggle="modal" data-target="#restoreBackup_{{ $key }}"  onclick="show_animation('{{$backup->klid}}');"--}}
                                        <a id="myButton" href="#!" data-toggle="modal" data-target="#restoreBackup_{{ $key }}"  onclick="show_animation('{{$backup['klid']}}');"
                                           {{--                                    <a href="#!" data-toggle="modal" data-target="#restoreBackup_{{ $key }}"--}}
                                       style="margin-left:10px; margin-right:10px; font-size: .75rem;font-weight: bold;"
                                       class="btn btn-warning">{{ translate('Restore Backups') }}</a>
                                @else
                                    <a href="#!" data-toggle="modal" data-target="#restoreBackup_{{ $key }}"
                                       style="margin-left:10px; margin-right:10px; font-size: .75rem;font-weight: bold;"
                                       class="disabled btn btn-warning">{{ translate('Restore Manually') }}</a>
                                @endif
                                <a href="#!" data-toggle="modal" data-target="#deleteBackup_{{ $key }}"
                                   style="margin-left:10px; margin-right:10px; font-size: .75rem;font-weight: bold;"
                                   class="btn btn-danger">{{ translate('Delete Backups') }}</a>

                                @if(1>2)
                                    <a href="#!" data-toggle="modal" data-target="#sendftp_{{ $key }}"
                                       style="margin-left:10px; margin-right:10px; font-size: .75rem;font-weight: bold;"
                                       class="btn btn-info">{{ translate('Send Via Ftp') }}</a>
                                @endif


                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                {{--            <div class="aiz-pagination">--}}
                {{--                {{ $backups->links() }}--}}
                {{--                {{ $backups->appends(request()->input())->links() }}--}}
                {{--                {!! $backups->render() !!}--}}
                {{--                {!! $backups->appends(Request::all())->links() !!}--}}
                {{--            </div>--}}
                <div class="aiz-pagination mt-4">
                    {{ $backups->appends(request()->input())->links() }}
                </div>


            </div>

        </form>


    </div>


    {{--    //create backup section--}}
    <div class="modal fade" id="addBackup" tabindex="-1" role="dialog" aria-labelledby="addBackup" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Generate New Backup') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form id="frm_backup" action="{{ route('backups.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group required">
                            <label for="name">{{ translate('Name') }}</label>
                            <input type="text" class="form-control" id="name" name="name"
                                   placeholder={{ translate('Name') }}>
                        </div>

                        <div class="form-group">
                            <h5 class="modal-title">{{ translate('Choose Your Backup Type') }}</h5>
                        </div>

                        <div class="radio-button">
                            <input type="radio" id="radio0" name="backuptype" value="0" checked>
                            <label for="radio0">{{ translate('DataBase And Folder') }}</label>
                            <input type="radio" id="radio1" name="backuptype" value="1">
                            <label for="radio1">{{ translate('Only DataBase') }}</label>
                            <input type="radio" id="radio2" name="backuptype" value="2">
                            <label for="radio2">{{ translate('Only Folder') }}</label>
                            <input type="radio" id="radio3" name="backuptype" value="3">
                            <label for="radio3">{{ translate('This Entire Website And DataBase') }}</label>
                            <input type="radio" id="radio4" name="backuptype" value="4">
                            <label for="radio4">{{ translate('Only This Entire Website') }}</label>
                        </div>
                        <input type="hidden" id="checkboxeslistzz" name="checkboxeslist" value="[]"/>
                        <input type="hidden" id="tablecount" name="tablecount" value="{{ count($tables) }}"/>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                    data-dismiss="modal">{{ translate('Cancel') }}</button>
                            <button type="submit"
                                    class="btn btn-success">{{ translate('Generate') }}</button>
                        </div>

                    </div>
                </form>
                <div id="PersianGulfBackup"></div>

            </div>
        </div>
    </div>

    {{--        //create Addons backup section--}}
    <div class="modal fade" id="addBackupAddons" tabindex="-1" role="dialog" aria-labelledby="addBackupAddons" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Generate New Addons Backup') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="frm_backupaddons" action="{{ route('backups.storeaddons') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group required">
                            <label for="name">{{ translate('Name') }}</label>
                            <input type="text" class="form-control" id="name" name="name"
                                   placeholder={{ translate('Name') }}>
                        </div>

                        <div class="form-group">
                            <h5 class="modal-title">{{ translate('Choose Your Backup Type') }}</h5>
                        </div>

                        <div class="radio-button">
                            <input type="radio" id="radio041" name="backuptype" value="5" checked>
                            <label for="radio041">{{ translate('Only Addons') }}</label>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                    data-dismiss="modal">{{ translate('Cancel') }}</button>
                            <button type="submit"
                                    class="btn btn-success">{{ translate('Generate') }}</button>
                        </div>

                    </div>
                </form>
                <div id="PersianGulfAddonsBackup"></div>

            </div>
        </div>
    </div>

    {{--    //check backup file and folder permission--}}
    <div class="modal fade" id="checkPermission" tabindex="-1" role="dialog" aria-labelledby="checkPermission"
         aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('File And Folder Permission') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>{{ translate('File or Folder') }}</th>
                            <th>{{ translate('Status') }}</th>
                        </tr>
                        </thead>
                        @php
                            //                        $required_paths = ['databasebackups','databasebackups/backup.json', 'public', 'app/Providers', 'app/Http/Controllers', 'storage', 'resources/views']
                                                    $required_paths = ['databasebackups','databasebackups/backup.json', base_path(), '.env',  'public', 'app/Providers', 'app/Http/Controllers', 'storage', 'resources/views']
                        @endphp
                        <tbody>
                        @foreach ($required_paths as $path)
                            <tr>
                                <td>{{ $path }}</td>
                                <td>
                                    @if(is_writable(base_path($path)))
                                        <i class="las la-check-circle la-2x text-success"></i>
                                    @else
                                        <i class="las la-times-circle la-2x text-danger"></i>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>


                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                                data-dismiss="modal">{{ translate('Close') }}</button>
                    </div>

                </div>
            </div>
        </div>

    </div>

    {{--    //set ftp settings--}}
    @if(1>2)
        <div class="modal fade" id="ftpsettings" tabindex="-2" role="dialog" aria-labelledby="ftpsettings"
             aria-hidden="true">

            <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Ftp Settings') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="card-body">
                        <form class="form-horizontal" action="{{ route('env_key_update.update') }}" method="POST">
                            @csrf
                            <div id="smtp">
                                <div class="form-group row">
                                    <input type="hidden" name="types[]" value="FTP_HOST">
                                    <div class="col-md-3">
                                        <label class="col-from-label">{{translate('FTP HOST')}}</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="FTP_HOST"
                                               value="{{  env('FTP_HOST') }}"
                                               placeholder="{{ translate('ftp.example.com') }}">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <input type="hidden" name="types[]" value="FTP_USERNAME">
                                    <div class="col-md-3">
                                        <label class="col-from-label">{{translate('FTP USERNAME')}}</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="FTP_USERNAME"
                                               value="{{  env('FTP_USERNAME') }}"
                                               placeholder="{{ translate('FTP USERNAME') }}">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <input type="hidden" name="types[]" value="FTP_PASSWORD">
                                    <div class="col-md-3">
                                        <label class="col-from-label">{{translate('FTP PASSWORD')}}</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="FTP_PASSWORD"
                                               value="{{  env('FTP_PASSWORD') }}"
                                               placeholder="{{ translate('FTP PASSWORD') }}">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <input type="hidden" name="types[]" value="FTP_PORT">
                                    <div class="col-md-3">
                                        <label class="col-from-label">{{translate('FTP PORT')}}</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="FTP_PORT"
                                               value="{{  env('FTP_PORT') }}"
                                               placeholder="{{ translate('Default Port 21') }}">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <input type="hidden" name="types[]" value="FTP_DEBUG">
                                    <label class="col-md-3 col-form-label">{{translate('Type')}}</label>
                                    <div class="col-md-9">
                                        <select class="form-control aiz-selectpicker mb-2 mb-md-0" name="FTP_DEBUG">
                                            <option value="1"
                                                    @if (env('FTP_DEBUG') == "1") selected @endif>{{ translate('true') }}</option>
                                            <option value="0"
                                                    @if (env('FTP_DEBUG') == "0") selected @endif>{{ translate('false') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <input type="hidden" name="types[]" value="PROJECT_ROOT">
                                    <div class="col-md-3">
                                        <label class="col-from-label">{{translate('PROJECT ROOT')}}</label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="PROJECT_ROOT"
                                               value="{{  env('PROJECT_ROOT') }}"
                                               placeholder="{{ translate('./public_html/your_project_name/') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mb-0 text-right">
                                <button type="submit" class="btn btn-primary">{{translate('Save Configuration')}}</button>
                            </div>
                        </form>
                    </div>


                </div>
            </div>

        </div>
    @endif

    @foreach ($backups as $key => $backup)

        {{--        //delete section--}}
        <div class="modal fade" id="deleteBackup_{{ $key }}" tabindex="-1" role="dialog"
             aria-labelledby="deleteBackup_{{ $key }}" aria-hidden="true">

            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('confirm delete') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    {{--                    <div class="modal-body">--}}
                    {{--                        {{ translate('Do you really want to delete this backup?') }}--}}
                    {{--                    </div>--}}
                    <div class="alert alert-danger" role="alert">
                        <b>{{ translate('Do you really want to delete this backup?') }}</b>
                    </div>


                    <form action="{{ route('backups.destroy') }}" method="POST">
                        @csrf
                        {!! method_field('DELETE') !!}
                        <div class="modal-body">

                            <input type="hidden" name="key" value="{{ $key }}"/>
                            <input type="hidden" name="backuptype" value="{{ $backup['type'] }}"/>
                            {{--                            // 0 means backup include folder and database, then user can select for delete folder or database or both of them--}}
                            @if ($backup['type'] == 0)
                                <div class="form-group">
                                    <h5 class="modal-title">{{ translate('Choose Your Delete Type') }}</h5>
                                </div>

                                <div class="radio-button">
                                    <input type="radio" id="radio00_{{ $key }}" name="deletetype" value="0" checked>
                                    <label for="radio00_{{ $key }}">{{ translate('DataBase And Folder') }}</label>
                                    <input type="radio" id="radio01_{{ $key }}" name="deletetype" value="1">
                                    <label for="radio01_{{ $key }}">{{ translate('Only DataBase') }}</label>
                                    <input type="radio" id="radio02_{{ $key }}" name="deletetype" value="2">
                                    <label for="radio02_{{ $key }}">{{ translate('Only Folder') }}</label>
                                </div>
                                {{--                            // 0 means backup include website and database, then user can select for delete website or database or both of them--}}
                            @elseif($backup['type'] == 3)
                                <div class="form-group">
                                    <h5 class="modal-title">{{ translate('Choose Your Delete Type') }}</h5>
                                </div>

                                <div class="radio-button">
                                    <input type="radio" id="radio000_{{ $key }}" name="deletetype" value="0" checked>
                                    <label for="radio000_{{ $key }}">{{ translate('DataBase And WebSite') }}</label>
                                    <input type="radio" id="radio001_{{ $key }}" name="deletetype" value="1">
                                    <label for="radio001_{{ $key }}">{{ translate('Only DataBase') }}</label>
                                    <input type="radio" id="radio002_{{ $key }}" name="deletetype" value="2">
                                    <label for="radio002_{{ $key }}">{{ translate('Only WebSite') }}</label>
                                </div>
                            @endif
                            {{--                            // in other wise type of backup, user can delete only website or only database or only folder--}}


                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                        data-dismiss="modal">{{ translate('Cancel') }}
                                </button>
                                <button type="submit" class="btn btn-danger">{{ translate('Delete') }}</button>
                            </div>
                        </div>


                    </form>
                </div>
            </div>
        </div>

        {{--        //restore section--}}
        <div class="modal fade" id="restoreBackup_{{ $key }}" tabindex="-1" role="dialog"
             aria-labelledby="restoreBackup_{{ $key }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Confirm Restore') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    {{--                    <div class="modal-body">--}}
                    {{--                        {{ translate('Do you really want to restore this backup?') }}--}}
                    {{--                    </div>--}}
                    <div class="alert alert-warning" role="alert">
                        <b>{{ translate('Do you really want to restore this backup?') }}</b>
                    </div>
                    <form id="frm_restore_{{ $backup['klid'] }}" action="{{ route('backups.restore') }}" method="POST">
                        @csrf
                        <input type="hidden" name="key" value="{{ $key }}"/>


                        {{--                        --}}
                        <input type="hidden" name="backuptype" value="{{ $backup['type'] }}"/>
                        <div class="modal-body">
                            {{--                            // 0 means backup include folder and database, then user can select for restore folder or database or both of them--}}
                            @if ($backup['type'] == 0)

                                <div class="form-group">
                                    <h5 class="modal-title">{{ translate('Choose Your Restore Type') }}</h5>
                                </div>

                                <div class="radio-button">
                                    <input type="radio" id="radio30_{{ $key }}" name="restoretype" value="0" checked>
                                    <label for="radio30_{{ $key }}">{{ translate('DataBase And Folder') }}</label>
                                    <input type="radio" id="radio31_{{ $key }}" name="restoretype" value="1">
                                    <label for="radio31_{{ $key }}">{{ translate('Only DataBase') }}</label>
                                    <input type="radio" id="radio32_{{ $key }}" name="restoretype" value="2">
                                    <label for="radio32_{{ $key }}">{{ translate('Only Folder') }}</label>
                                </div>
                                {{--                            // 0 means backup include website and database, then user can select for restore website or database or both of them--}}
                            @elseif($backup['type'] == 3)

                                <div class="alert alert-danger" role="alert">
                                    <b>{{ translate('You can only restore the database') }}</b><br>
                                    <b>{{ translate('Because restoring the site from the beginning must be done manually and outside of the program') }}</b>
                                </div>
                                <div class="alert alert-primary" role="alert">
                                    <b>{{ translate('Therefore, only restoring the database is allowed and selected by default. other options are disabled') }}</b>
                                </div>
                                {{--                                <div class="form-group">--}}
                                {{--                                    <h5 class="modal-title">{{ translate('Therefore, only restoring the database is allowed and selected by default, and other options are disabled') }}</h5>--}}
                                {{--                                </div>--}}
                                <div class="radio-button">
                                    <input type="radio" id="radio030_{{ $key }}" name="restoretype" value="0" disabled>
                                    <label for="radio030_{{ $key }}">{{ translate('DataBase And WebSite') }}</label>
                                    <input type="radio" id="radio031_{{ $key }}" name="restoretype" value="1" checked>
                                    <label for="radio031_{{ $key }}">{{ translate('Only DataBase') }}</label>
                                    <input type="radio" id="radio032_{{ $key }}" name="restoretype" value="2" disabled>
                                    <label for="radio032_{{ $key }}">{{ translate('Only WebSite') }}</label>
                                </div>
                            @endif

                            {{--                        --}}





                            {{--                            @if ($backup['type'] == 0)--}}
                            {{--                                <div class="form-group">--}}
                            {{--                                    <h5 class="modal-title">{{ translate('Choose Your Restore Type') }}</h5>--}}
                            {{--                                </div>--}}

                            {{--                                <div class="radio-button">--}}
                            {{--                                    <input type="radio" id="radio31" name="restoretype" value="0" checked>--}}
                            {{--                                    <label for="radio31">{{ translate('DataBase And Folder') }}</label>--}}
                            {{--                                    <input type="radio" id="radio32" name="restoretype" value="1">--}}
                            {{--                                    <label for="radio32">{{ translate('Only DataBase') }}s</label>--}}
                            {{--                                    <input type="radio" id="radio33" name="restoretype" value="2">--}}
                            {{--                                    <label for="radio33">{{ translate('Only Folder') }}</label>--}}
                            {{--                                    <input type="radio" id="radio44" name="restoretype" value="3">--}}
                            {{--                                    <label for="radio44">{{ translate('This entire website And DataBase') }}</label>--}}
                            {{--                                </div>--}}
                            {{--                            @endif--}}

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                        data-dismiss="modal">{{ translate('Cancel') }}</button>
                                <button type="submit" class="btn btn-warning">{{ translate('Restore') }}</button>
                            </div>
                        </div>

                    </form>
                    <div id="PersianGulfRestore_{{ $backup['klid'] }}"></div>

                </div>
            </div>
        </div>

        {{--        //send via ftp section--}}
        @if(1>2)
            <div class="modal fade" id="sendftp_{{ $key }}" tabindex="-1" role="dialog"
                 aria-labelledby="sendftp_{{ $key }}" aria-hidden="true">

                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ translate('confirm send') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        {{--                    <div class="modal-body">--}}
                        {{--                        {{ translate('Do you really want to send this backup?') }}--}}
                        {{--                    </div>--}}
                        <div class="alert alert-primary" role="alert">
                            <b>{{ translate('Do you really want to send this backup?') }}</b>
                        </div>


                        <form action="{{ route('backups.send') }}" method="POST">
                            @csrf
                            {!! method_field('SEND') !!}
                            <div class="modal-body">

                                <input type="hidden" name="key" value="{{ $key }}"/>
                                <input type="hidden" name="backuptype" value="{{ $backup['type'] }}"/>

                                {{--                            // 0 means backup include folder and database, then user can select for send folder or database or both of them--}}
                                @if ($backup['type'] == 0)
                                    <div class="form-group">
                                        <h5 class="modal-title">{{ translate('Choose Your Send Type') }}</h5>
                                    </div>

                                    <div class="radio-button">
                                        <input type="radio" id="radios00" name="sendtype" value="0" checked>
                                        <label for="radios00">{{ translate('DataBase And Folder') }}</label>
                                        <input type="radio" id="radios01" name="sendtype" value="1">
                                        <label for="radios01">{{ translate('Only DataBase') }}</label>
                                        <input type="radio" id="radios02" name="sendtype" value="2">
                                        <label for="radios02">{{ translate('Only Folder') }}</label>
                                    </div>
                                    {{--                            // 0 means backup include website and database, then user can select for send website or database or both of them--}}
                                @elseif($backup['type'] == 3)
                                    <div class="form-group">
                                        <h5 class="modal-title">{{ translate('Choose Your Send Type') }}</h5>
                                    </div>

                                    <div class="radio-button">
                                        <input type="radio" id="radios000" name="sendtype" value="0" checked>
                                        <label for="radios000">{{ translate('DataBase And WebSite') }}</label>
                                        <input type="radio" id="radios001" name="sendtype" value="1">
                                        <label for="radios001">{{ translate('Only DataBase') }}</label>
                                        <input type="radio" id="radios002" name="sendtype" value="2">
                                        <label for="radios002">{{ translate('Only WebSite') }}</label>
                                    </div>
                                @endif
                                {{--                            // in other wise type of backup, user can send only website or only database or only folder--}}


                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                            data-dismiss="modal">{{ translate('Cancel') }}
                                    </button>
                                    <button type="submit" class="btn btn-info">{{ translate('Send') }}</button>
                                </div>
                            </div>


                        </form>
                    </div>
                </div>
            </div>
        @endif

    @endforeach

@endsection


@section('script')
    <script>

        var thisIsGlobalForm= null;
        var thisIsGlobalDiv= null;

        //close side bar menu when form loaded
        $(document).ready(function(){
            $('body').addClass('side-menu-closed');
        });

        function show_animation(e) {
            // console.log(e);
            var nn='frm_restore_'+e;
            // e.preventDefault();   // use this to NOT go to href site
            var frm_restorex = document.getElementById(nn);
            thisIsGlobalForm = document.getElementById(nn);
            console.log(frm_restorex);
            thisIsGlobalForm.addEventListener('submit', bmdLoading2);
            thisIsGlobalDiv ='#PersianGulfRestore_'+e;
        }

        //add number row to table
        //addNumeration("tablezz")
        // var addNumeration = function (cl) {
        //     var table = document.querySelector('table.' + cl)
        //     var trs = table.querySelectorAll('tr')
        //     var counter = 1
        //
        //     Array.prototype.forEach.call(trs, function (x, i) {
        //         var firstChild = x.children[0]
        //         if (firstChild.tagName === 'TD') {
        //             var cell = document.createElement('td')
        //             cell.textContent = counter++
        //             x.insertBefore(cell, firstChild)
        //         } else {
        //             firstChild.setAttribute('colspan', 2)
        //         }
        //     })
        // }




        //if clicked on #ch_all, check all tables in .ch_tables
        var ch_all = document.getElementById('ch_all');
        if (ch_all) {
            var ch_btn = ch_all.querySelector('input');
            var tables = document.querySelectorAll('#frm_cht .ch_tables input');
            ch_all.addEventListener('click', function () {
                if (tables) {
                    for (var i = 0; i < tables.length; i++) tables[i].checked = ch_btn.checked;
                    // update_all_status();
                }
            });
        }

        var frm_backup = document.getElementById('frm_backup');
        // var frm_restore = document.getElementById('frm_restore');
        // var frm_restorev = document.getElementById('frm_restore');
        var frm_backupaddons = document.getElementById('frm_backupaddons');

        frm_backup.addEventListener('submit', bmdLoading1);
        // console.log(frm_backup);
        // frm_restore.addEventListener('submit', bmdLoading2);
        // console.log(frm_restore);
        frm_backupaddons.addEventListener('submit', bmdLoading3);
        // console.log(frm_backupaddons);
        // console.log(thisIsGlobalForm);



        //display loading message
        function bmdLoading1() {
            var divloading1 = '';
            divloading1 = '<div class="currently-loading"></div>'
            $("#PersianGulfBackup").html(divloading1);
        }

        //display loading message
        function bmdLoading2() {
            var divloading2 = '';
            divloading2 = '<div class="currently-loading"></div>'

            // divloading2x ='<ul class="cssload-cssload-ballsncups">'
            // divloading2x +='<li>'
            // divloading2x +='<div class="cssload-circle"></div>'
            // divloading2x +='<div class="cssload-ball"></div>'
            // divloading2x +='</li>'
            // divloading2x +='<li>'
            // divloading2x +='<div class="cssload-circle"></div>'
            // divloading2x +='<div class="cssload-ball"></div>'
            // divloading2x +='</li>'
            // divloading2x +='<li>'
            // divloading2x +='<div class="cssload-circle"></div>'
            // divloading2x +='<div class="cssload-ball"></div>'
            // divloading2x +='</li>'
            // divloading2x +='<li>'
            // divloading2x +='<div class="cssload-circle"></div>'
            // divloading2x +='<div class="cssload-ball"></div>'
            // divloading2x +='</li>'
            // divloading2x +='<li>'
            // divloading2x +='<div class="cssload-circle"></div>'
            // divloading2x +='<div class="cssload-ball"></div>'
            // divloading2x +='</li>'
            // divloading2x +='<li>'
            // divloading2x +='<div class="cssload-circle"></div>'
            // divloading2x +='<div class="cssload-ball"></div>'
            // divloading2x +='</li>'
            // divloading2x +='<li>'
            // divloading2x +='<div class="cssload-circle"></div>'
            // divloading2x +='<div class="cssload-ball"></div>'
            // divloading2x +='</li>'
            // divloading2x +='<li>'
            // divloading2x +='<div class="cssload-circle"></div>'
            // divloading2x +='<div class="cssload-ball"></div>'
            // divloading2x +='</li>'
            // divloading2x +='</ul>'
            //



            $(thisIsGlobalDiv).html(divloading2);
        }

        //display loading message
        function bmdLoading3() {
            var divloading3 = '';
            divloading3 = '<div class="currently-loading"></div>'
            $("#PersianGulfAddonsBackup").html(divloading3);
        }

        function update_status(el, tablecount) {
            var arrayx = [];
            // if (el.checked) {
            //     var status = 1;
            // } else {
            //     var status = 0;
            // }
            var tables = document.querySelectorAll('#frm_cht .ch_tables input');
            for (var n = 0; n < tables.length; n++) {
                if (tables[n].checked) {
                    arrayx.push(tables[n].value)
                }
            }
            $('input[type=hidden][name="checkboxeslist"]').val(JSON.stringify(arrayx));

            console.log(arrayx);
            console.log(tablecount + ' <> ' + arrayx.length);

            var baba = '';
            if (tablecount === arrayx.length) {
                baba = '<h3 style="color: #cc2e2e">There are no tables to backup</h3>';
            } else {
                if (arrayx.length > 0) {
                    baba = '<h3 style="color: #e7a042">A number of tables have been selected for exclude from backup</h3>';
                } else {
                    baba = '<h3 style="color: #04a111">all tables have been selected for backup</h3>';
                }
            }
            $("#persian").html(baba);

            console.log($("input[id=checkboxeslistzz]").val());
        }

        function update_all_status(tablecount) {
            // const tablecount=104;
            var arrayx = [];
            var tables = document.querySelectorAll('#frm_cht .ch_tables input');
            for (var n = 0; n < tables.length; n++) {
                if (tables[n].checked) {
                    arrayx.push(tables[n].value)
                }
            }
            $('input[type=hidden][name="checkboxeslist"]').val(JSON.stringify(arrayx));

            console.log(arrayx);
            console.log(tablecount + ' <> ' + arrayx.length);

            var baba = '';
            if (tablecount === arrayx.length) {
                baba = '<h3 style="color: #cc2e2e">There are no tables to backup</h3>';
            } else {
                if (arrayx.length > 0) {
                    baba = '<h3 style="color: #e7a042">A number of tables have been selected for exclude from backup</h3>';
                } else {
                    baba = '<h3 style="color: #04a111">all tables have been selected for backup</h3>';
                }
            }
            $("#persian").html(baba);


            console.log($("input[id=checkboxeslistzz]").val());
        }


        function show_hide_selection(el) {
            if (el.checked) {
                $("#table_selection").show();
            } else {
                $("#table_selection").hide();
            }
        }


        //reset advance table selection checkbox to uncheck when form refreshed
        $(document).ready(function () {
            var adv_check = document.getElementById('advcheck');
            var ch_btnx = adv_check.querySelector('input');
            ch_btnx.checked = false;
        });


        //old method for sort date in table
        // function sort_backups(el) {
        //     $('#sort_backups').submit();
        // }



    </script>
@endsection
