<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Administrator - @yield('title')</title>
  @livewireStyles
  <!-- plugins:css -->
  <link rel="stylesheet" href="{{asset('assets/vendors/ti-icons/css/themify-icons.css')}}">
  <link rel="stylesheet" href="{{asset('assets/vendors/base/vendor.bundle.base.css')}}">
  <!-- endinject -->
  <!-- plugin css for this page -->
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <link rel="stylesheet" href="{{asset('assets/css/style.css')}}">
  <!-- endinject -->
  <link rel="shortcut icon" href="{{asset('assets/images/favicon.png')}}"/>
</head>
<body>
  <div class="container-scroller">
    <!-- navbar -->
    <!-- partial:partial/navbar.blade.php -->
    @include('partial.navbar')
    <!-- partial -->

    <div class="container-fluid page-body-wrapper">
      <!-- sidebar -->
      <!-- partial:partial/sidebar.blade.php -->
      @include('partial.sidebar')
      <!-- partial -->
      <div class="main-panel">
        <div class="container">
          @if(session('message'))
            <div class="alert alert-info">{{session('message')}}</div>
          @endif

          @if(session('success'))
            <div class="alert alert-success">{{session('success')}}</div>
          @endif

          @if(session('warning'))
            <div class="alert alert-warning">{{session('warning')}}</div>
          @endif

          @if(session('error'))
            <div class="alert alert-danger">{{session('error')}}</div>
          @endif

          @if($errors->any())
          <div class="alert alert-danger">
            <ul class="my-0">
              @foreach( $errors->all() as $error )
                <li>{{$errors}}</li>
              @endforeach
            </ul>
          </div>
          @endif
        </div>
        @yield('content')
        <!-- footer -->
        <!-- partial:partial/footer.blade.php -->
        @include('partial.footer')
        <!-- partial -->
      </div>
      <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->

  @livewireScripts
  <!-- plugins:js -->
  <script src="{{asset('assets/vendors/base/vendor.bundle.base.js')}}"></script>
  <!-- endinject -->
  <!-- Plugin js for this page-->
  <script src="{{asset('assets/vendors/chart.js/Chart.min.js')}}"></script>
  <script src="{{asset('assets/js/jquery.cookie.js')}}" type="text/javascript"></script>
  <!-- End plugin js for this page-->
  <!-- inject:js -->
  <script src="{{asset('assets/js/off-canvas.js')}}"></script>
  <script src="{{asset('assets/js/hoverable-collapse.js')}}"></script>
  <script src="{{asset('assets/js/template.js')}}"></script>
  <script src="{{asset('assets/js/todolist.js')}}"></script>
  <!-- endinject -->
  <!-- Custom js for this page-->
  <script src="{{asset('js/dashboard.js')}}"></script>
  <!-- End custom js for this page-->
</body>

</html>

