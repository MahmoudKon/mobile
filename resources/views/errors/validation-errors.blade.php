<!-- display errors of validation -->
@if (isset($errors))
  <div class="alert alert-danger">
      @foreach ($errors->all() as $error)
          {{ $error }}<br>        
      @endforeach
  </div>
@endif