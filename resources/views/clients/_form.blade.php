<div class="mb-3">
    <label class="form-label">Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
        value="{{ old('name', $client->name ?? '') }}">
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Email <span class="text-danger">*</span></label>
    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
        value="{{ old('email', $client->email ?? '') }}">
    @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Phone</label>
    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
        value="{{ old('phone', $client->phone ?? '') }}">
    @error('phone')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Company Name</label>
    <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror"
        value="{{ old('company_name', $client->company_name ?? '') }}">
    @error('company_name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Billing Address</label>
    <textarea name="billing_address" class="form-control @error('billing_address') is-invalid @enderror" rows="3">{{ old('billing_address', $client->billing_address ?? '') }}</textarea>
    @error('billing_address')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
