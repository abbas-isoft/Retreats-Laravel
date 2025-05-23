<!-- <x-app title="Price Listing">
  <h2>
    Price Listing
    <a href="{{ route('price.listing.data') }}">Fetch & Update Price Listing</a>
  </h2>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
  @endif

  <p><strong>Property ID:</strong> {{ $priceData['propertyId'] ?? 'N/A' }}</p>
  <p><strong>Currency:</strong> {{ $priceData['prices']['currency'] ?? 'N/A' }}</p>
  <p><strong>Last Updated:</strong> {{ $updatedAt ?? 'N/A' }}</p>

  @if (!empty($priceData['prices']['fees']))
    <h3>Fees</h3>
    <table>
      <thead>
        <tr>
          <th>Name</th><th>Type</th><th>Price</th><th>VAT</th><th>Payable With</th><th>Refundable</th><th>Hidden</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($priceData['prices']['fees'] as $fee)
          <tr>
            <td>{{ $fee['name'] ?? 'N/A' }}</td>
            <td>{{ $fee['type'] ?? 'N/A' }}</td>
            <td>{{ $fee['price'] ?? 'N/A' }}</td>
            <td>{{ $fee['vat'] ?? 'N/A' }}</td>
            <td>{{ $fee['feePayableWith'] ?? 'N/A' }}</td>
            <td>{{ isset($fee['refundable']) && $fee['refundable'] ? 'Yes' : 'No' }}</td>
            <td>{{ isset($fee['hidden']) && $fee['hidden'] ? 'Yes' : 'No' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  @if (!empty($priceData['prices']['priceLos']))
    <h3>Price LOS Entries</h3>
    <table>
      <thead>
        <tr>
          <th>Date</th><th>LOS Values</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($priceData['prices']['priceLos'] as $losEntry)
          @php
            $parts = explode(',', $losEntry);
            $date = array_shift($parts);
            $losValues = implode(', ', $parts);
          @endphp
          <tr>
            <td>{{ $date }}</td>
            <td>{{ $losValues }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  @if (!empty($priceData['prices']['paymentSchedule']))
    <h3>Payment Schedule</h3>
    <table>
      <tr>
        <th>Deposit Type</th>
        <td>{{ $priceData['prices']['paymentSchedule']['deposit']['depositType'] ?? 'N/A' }}</td>
      </tr>
      <tr>
        <th>Deposit Value</th>
        <td>{{ $priceData['prices']['paymentSchedule']['deposit']['value'] ?? 'N/A' }}%</td>
      </tr>
      <tr>
        <th>Security Deposit</th>
        <td>{{ $priceData['prices']['paymentSchedule']['securityDeposit']['value'] ?? 'N/A' }}</td>
      </tr>
      <tr>
        <th>Balance Due Days</th>
        <td>{{ $priceData['prices']['paymentSchedule']['balanceDueDays'] ?? 'N/A' }}</td>
      </tr>
    </table>
  @endif
</x-app> -->






<!-- <x-app title="Price Listing">
 
<h2>
  Price Listing
  <form action="{{ route('price.listing.data') }}" method="GET" style="display: inline;">
    <button type="submit" style="margin-left: 20px;">Fetch & Update Price Listing</button>
  </form>
</h2>


  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
  @endif

  <p><strong>Property ID:</strong> {{ $priceData['propertyId'] ?? 'N/A' }}</p>
  <p><strong>Currency:</strong> {{ $priceData['prices']['currency'] ?? 'N/A' }}</p>
  <p><strong>Last Updated:</strong> {{ $updatedAt ?? 'N/A' }}</p>

  @if (!empty($priceData['prices']['fees']))
    <h3>Fees</h3>
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Type</th>
          <th>Price</th>
          <th>VAT</th>
          <th>Payable With</th>
          <th>Refundable</th>
          <th>Hidden</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($priceData['prices']['fees'] as $fee)
          <tr>
            <td>{{ $fee['name'] ?? 'N/A' }}</td>
            <td>{{ $fee['type'] ?? 'N/A' }}</td>
            <td>{{ $fee['price'] ?? 'N/A' }}</td>
            <td>{{ $fee['vat'] ?? 'N/A' }}</td>
            <td>{{ $fee['feePayableWith'] ?? 'N/A' }}</td>
            <td>{{ isset($fee['refundable']) && $fee['refundable'] ? 'Yes' : 'No' }}</td>
            <td>{{ isset($fee['hidden']) && $fee['hidden'] ? 'Yes' : 'No' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @else
    <p><em>No fee data found.</em></p>
  @endif

  @if (!empty($priceData['prices']['priceLos']))
    <h3>Price LOS Entries</h3>
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>LOS Values</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($priceData['prices']['priceLos'] as $losEntry)
          @php
            $parts = explode(',', $losEntry);
            $date = array_shift($parts);
            $losValues = implode(', ', $parts);
          @endphp
          <tr>
            <td>{{ $date }}</td>
            <td>{{ $losValues }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @else
    <p><em>No LOS data available.</em></p>
  @endif

  @if (!empty($priceData['prices']['paymentSchedule']))
    <h3>Payment Schedule</h3>
    <table>
      <tr>
        <th>Deposit Type</th>
        <td>{{ $priceData['prices']['paymentSchedule']['deposit']['depositType'] ?? 'N/A' }}</td>
      </tr>
      <tr>
        <th>Deposit Value</th>
        <td>{{ $priceData['prices']['paymentSchedule']['deposit']['value'] ?? 'N/A' }}%</td>
      </tr>
      <tr>
        <th>Security Deposit</th>
        <td>{{ $priceData['prices']['paymentSchedule']['securityDeposit']['value'] ?? 'N/A' }}</td>
      </tr>
      <tr>
        <th>Balance Due Days</th>
        <td>{{ $priceData['prices']['paymentSchedule']['balanceDueDays'] ?? 'N/A' }}</td>
      </tr>
    </table>
  @else
    <p><em>No payment schedule found.</em></p>
  @endif
</x-app> -->






<!-- <x-app title="Price Listing">

<h2>
  Price Listing
  <form action="{{ route('price.fetch') }}" method="GET" style="display: inline;">
    <button type="submit" style="margin-left: 20px;">Fetch & Update Price Listing</button>
  </form>
</h2>

@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
  <div class="alert alert-error">{{ session('error') }}</div>
@endif

@foreach ($configurations as $config)
  @php
    $priceData = json_decode($config->raw_data, true);
  @endphp

  <div style="border:1px solid #ccc; margin-bottom: 30px; padding: 15px;">
    <h3>Property Price Info</h3>
    <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
      <tr><th>Property ID</th><td>{{ $config->property_id }}</td></tr>
      <tr><th>Currency</th><td>{{ $config->currency ?? 'N/A' }}</td></tr>
      <tr><th>Balance Due Days</th><td>{{ $config->balance_due_days ?? 'N/A' }}</td></tr>
      <tr><th>Deposit Type</th><td>{{ $config->deposit_type ?? 'N/A' }}</td></tr>
      <tr><th>Deposit Value</th><td>{{ $config->deposit_value ?? 'N/A' }}</td></tr>
      <tr><th>Security Deposit</th><td>{{ $config->security_deposit ?? 'N/A' }}</td></tr>
      <tr><th>Last Updated</th><td>{{ $config->updated_at }}</td></tr>
    </table>

    {{-- Fees Section --}}
    @if (!empty($priceData['prices']['fees']))
      <h4 style="margin-top: 20px;">Fees</h4>
      <table border="1" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead>
          <tr>
            <th>Name</th><th>Type</th><th>Price</th><th>VAT</th><th>Payable With</th><th>Refundable</th><th>Hidden</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($priceData['prices']['fees'] as $fee)
            <tr>
              <td>{{ $fee['name'] ?? 'N/A' }}</td>
              <td>{{ $fee['type'] ?? 'N/A' }}</td>
              <td>{{ $fee['price'] ?? 'N/A' }}</td>
              <td>{{ $fee['vat'] ?? 'N/A' }}</td>
              <td>{{ $fee['feePayableWith'] ?? 'N/A' }}</td>
              <td>{{ isset($fee['refundable']) && $fee['refundable'] ? 'Yes' : 'No' }}</td>
              <td>{{ isset($fee['hidden']) && $fee['hidden'] ? 'Yes' : 'No' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <p><em>No fee data found.</em></p>
    @endif

    {{-- LOS Section --}}
    @if (!empty($priceData['prices']['priceLos']))
      <h4 style="margin-top: 20px;">Price LOS Entries</h4>
      <table border="1" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead><tr><th>Date</th><th>LOS Values</th></tr></thead>
        <tbody>
          @foreach ($priceData['prices']['priceLos'] as $losEntry)
            @php
              $parts = explode(',', $losEntry);
              $date = array_shift($parts);
              $losValues = implode(', ', $parts);
            @endphp
            <tr>
              <td>{{ $date }}</td>
              <td>{{ $losValues }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif

    {{-- Payment Schedule Section --}}
    @if (!empty($priceData['prices']['paymentSchedule']))
      <h4 style="margin-top: 20px;">Payment Schedule</h4>
      <table border="1" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <tr>
          <th>Deposit Type</th>
          <td>{{ $priceData['prices']['paymentSchedule']['deposit']['depositType'] ?? 'N/A' }}</td>
        </tr>
        <tr>
          <th>Deposit Value</th>
          <td>{{ $priceData['prices']['paymentSchedule']['deposit']['value'] ?? 'N/A' }}%</td>
        </tr>
        <tr>
          <th>Security Deposit</th>
          <td>{{ $priceData['prices']['paymentSchedule']['securityDeposit']['value'] ?? 'N/A' }}</td>
        </tr>   
        <tr>
          <th>Balance Due Days</th>
          <td>{{ $priceData['prices']['paymentSchedule']['balanceDueDays'] ?? 'N/A' }}</td>
        </tr>
      </table>
    @else
      <p><em>No payment schedule found.</em></p>
    @endif

  </div>
@endforeach

</x-app> -->






<!-- <x-app title="Price Listing">
  <h2>
    Price Listing
    <form action="{{ route('price.fetch') }}" method="GET" style="display: inline;">
      @csrf
      <button type="submit" style="margin-left: 20px;">Fetch & Update Price Listing</button>
    </form>
  </h2>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
  @endif

  @foreach ($configurations as $config)
    @php
      $priceData = json_decode($config->raw_data, true);
      $fees = $priceData['prices']['fees'] ?? [];
      $losEntries = $priceData['prices']['priceLos'] ?? [];
      $paymentSchedule = $priceData['prices']['paymentSchedule'] ?? [];
    @endphp

    <div style="border:1px solid #ccc; margin-bottom: 30px; padding: 15px;">
      <h3>Property Price Info</h3>
      <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <tr><th>Property ID</th><td>{{ $config->property_id }}</td></tr>
        <tr><th>Currency</th><td>{{ $config->currency ?? 'N/A' }}</td></tr>
        <tr><th>Balance Due Days</th><td>{{ $config->balance_due_days ?? 'N/A' }}</td></tr>
        <tr><th>Deposit Type</th><td>{{ $config->deposit_type ?? 'N/A' }}</td></tr>
        <tr><th>Deposit Value</th><td>{{ $config->deposit_value ?? 'N/A' }}</td></tr>
        <tr><th>Security Deposit</th><td>{{ $config->security_deposit ?? 'N/A' }}</td></tr>
        <tr><th>Last Updated</th><td>{{ $config->updated_at }}</td></tr>
      </table>

      {{-- Fees Section --}}
      @if (!empty($fees))
        <h4 style="margin-top: 20px;">Fees</h4>
        <table border="1" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse;">
          <thead>
            <tr>
              <th>Name</th>
              <th>Type</th>
              <th>Price</th>
              <th>VAT</th>
              <th>Payable With</th>
              <th>Refundable</th>
              <th>Hidden</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($fees as $fee)
              <tr>
                <td>{{ $fee['name'] ?? 'N/A' }}</td>
                <td>{{ $fee['type'] ?? 'N/A' }}</td>
                <td>{{ $fee['price'] ?? 'N/A' }}</td>
                <td>{{ $fee['vat'] ?? 'N/A' }}</td>
                <td>{{ $fee['feePayableWith'] ?? 'N/A' }}</td>
                <td>{{ isset($fee['refundable']) ? ($fee['refundable'] ? 'Yes' : 'No') : 'N/A' }}</td>
                <td>{{ isset($fee['hidden']) ? ($fee['hidden'] ? 'Yes' : 'No') : 'N/A' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @else
        <p><em>No fee data found.</em></p>
      @endif

      {{-- LOS Section --}}
      @if (!empty($losEntries))
        <h4 style="margin-top: 20px;">Price LOS Entries</h4>
        <table border="1" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse;">
          <thead><tr><th>Date</th><th>LOS Values</th></tr></thead>
          <tbody>
            @foreach ($losEntries as $entry)
              @php
                $parts = explode(',', $entry);
                $date = array_shift($parts);
                $values = implode(', ', $parts);
              @endphp
              <tr>
                <td>{{ $date }}</td>
                <td>{{ $values }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @else
        <p><em>No LOS data available.</em></p>
      @endif

      {{-- Payment Schedule --}}
      @if (!empty($paymentSchedule))
        <h4 style="margin-top: 20px;">Payment Schedule</h4>
        <table border="1" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse;">
          <tr><th>Deposit Type</th><td>{{ $paymentSchedule['deposit']['depositType'] ?? 'N/A' }}</td></tr>
          <tr><th>Deposit Value</th><td>{{ $paymentSchedule['deposit']['value'] ?? 'N/A' }}%</td></tr>
          <tr><th>Security Deposit</th><td>{{ $paymentSchedule['securityDeposit']['value'] ?? 'N/A' }}</td></tr>
          <tr><th>Balance Due Days</th><td>{{ $paymentSchedule['balanceDueDays'] ?? 'N/A' }}</td></tr>
        </table>
      @else
        <p><em>No payment schedule found.</em></p>
      @endif
    </div>
  @endforeach
</x-app> -->





<x-app title="Price Listings">
  <h2>
    Price Listing
    <a href="{{ route('price.fetch') }}" style="margin-left: 20px;">Fetch & Update Price Listing</a>
  </h2>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
  @endif

  @forelse ($configurations as $config)
    @php
      $priceData = json_decode($config->raw_data, true);
      $fees = $priceData['prices']['fees'] ?? [];
      $losEntries = $priceData['prices']['priceLos'] ?? [];
      $paymentSchedule = $priceData['prices']['paymentSchedule'] ?? [];
    @endphp

    <div style="border:1px solid #ccc; margin-bottom: 30px; padding: 15px;">
      <h3>Property Price Info</h3>
      <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <tr><th>Property ID</th><td>{{ $config->property_id }}</td></tr>
        <tr><th>Currency</th><td>{{ $config->currency ?? 'N/A' }}</td></tr>
        <tr><th>Balance Due Days</th><td>{{ $config->balance_due_days ?? 'N/A' }}</td></tr>
        <tr><th>Deposit Type</th><td>{{ $config->deposit_type ?? 'N/A' }}</td></tr>
        <tr><th>Deposit Value</th><td>{{ $config->deposit_value ?? 'N/A' }}</td></tr>
        <tr><th>Security Deposit</th><td>{{ $config->security_deposit ?? 'N/A' }}</td></tr>
        <tr><th>Last Updated</th><td>{{ $config->updated_at }}</td></tr>
      </table>

      {{-- Fees --}}
      @if (!empty($fees))
        <h4 style="margin-top: 20px;">Fees</h4>
        <table border="1" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse;">
          <thead>
            <tr>
              <th>Name</th>
              <th>Type</th>
              <th>Price</th>
              <th>VAT</th>
              <th>Payable With</th>
              <th>Refundable</th>
              <th>Hidden</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($fees as $fee)
              <tr>
                <td>{{ $fee['name'] ?? 'N/A' }}</td>
                <td>{{ $fee['type'] ?? 'N/A' }}</td>
                <td>{{ $fee['price'] ?? 'N/A' }}</td>
                <td>{{ $fee['vat'] ?? 'N/A' }}</td>
                <td>{{ $fee['feePayableWith'] ?? 'N/A' }}</td>
                <td>{{ isset($fee['refundable']) ? ($fee['refundable'] ? 'Yes' : 'No') : 'N/A' }}</td>
                <td>{{ isset($fee['hidden']) ? ($fee['hidden'] ? 'Yes' : 'No') : 'N/A' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @else
        <p><em>No fee data found.</em></p>
      @endif

      {{-- LOS Entries --}}
      @if (!empty($losEntries))
        <h4 style="margin-top: 20px;">Price LOS Entries</h4>
        <table border="1" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse;">
          <thead><tr><th>Date</th><th>LOS Values</th></tr></thead>
          <tbody>
            @foreach ($losEntries as $entry)
              @php
                $parts = explode(',', $entry);
                $date = array_shift($parts);
                $values = implode(', ', $parts);
              @endphp
              <tr>
                <td>{{ $date }}</td>
                <td>{{ $values }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @else
        <p><em>No LOS data available.</em></p>
      @endif

      {{-- Payment Schedule --}}
      @if (!empty($paymentSchedule))
        <h4 style="margin-top: 20px;">Payment Schedule</h4>
        <table border="1" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse;">
          <tr><th>Deposit Type</th><td>{{ $paymentSchedule['deposit']['depositType'] ?? 'N/A' }}</td></tr>
          <tr><th>Deposit Value</th><td>{{ $paymentSchedule['deposit']['value'] ?? 'N/A' }}%</td></tr>
          <tr><th>Security Deposit</th><td>{{ $paymentSchedule['securityDeposit']['value'] ?? 'N/A' }}</td></tr>
          <tr><th>Balance Due Days</th><td>{{ $paymentSchedule['balanceDueDays'] ?? 'N/A' }}</td></tr>
        </table>
      @else
        <p><em>No payment schedule found.</em></p>
      @endif
    </div>
  @empty
    <p>No price listings found.</p>
  @endforelse
</x-app>




