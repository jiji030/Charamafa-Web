<template>
  <div v-if="isClient">
    <div v-if="!authStore.user" class="min-h-screen bg-gray-100 flex items-center justify-center">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-700 mx-auto mb-4"></div>
        <p class="text-gray-600">Loading...</p>
      </div>
    </div>

    <div v-else class="min-h-screen bg-gray-100">
      <header class="bg-green-700 text-white shadow-md">
        <div class="container mx-auto px-4 py-4">
          <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center overflow-hidden">
                <img src="/charmafa-logo.png" alt="CHARMAFA" class="w-10 h-10 object-contain" />
              </div>
              <div>
                <h1 class="text-2xl font-bold">CHARMAFA</h1>
                <p class="text-sm text-green-100">A Waterworks Service Association</p>
              </div>
            </div>
            <button
              @click="handleLogout"
              class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors"
            >
              Logout
            </button>
          </div>
        </div>
      </header>

      <nav class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4">
          <div class="flex gap-1">
            <button
              v-for="tab in tabs"
              :key="tab.path"
              @click="navigateTo(tab.path)"
              :class="[
                'px-6 py-3 font-medium transition-colors',
                currentPath.startsWith(tab.path)
                  ? 'text-green-700 border-b-2 border-green-700'
                  : 'text-gray-600 hover:text-green-600'
              ]"
            >
              {{ tab.label }}
            </button>
          </div>
        </div>
      </nav>

      <main class="container mx-auto px-4 py-8">
        <button
          @click="navigateTo('/president/members')"
          class="mb-6 flex items-center gap-2 text-green-700 hover:text-green-800 font-medium"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Back to Members List
        </button>

        <div class="bg-white rounded-lg shadow overflow-hidden">
          <div class="p-6 border-b bg-green-50">
            <h2 class="text-2xl font-bold text-gray-800">Edit Member Information</h2>
            <p class="text-sm text-gray-600 mt-1">Update member details below</p>
          </div>

          <div v-if="loading" class="p-12 text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-700 mx-auto mb-4"></div>
            <p class="text-gray-600">Loading member data...</p>
          </div>

          <div v-else-if="loadError" class="p-12 text-center">
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg inline-block">
              {{ loadError }}
            </div>
          </div>

          <div v-else class="p-6">
            <form @submit.prevent="updateMember" class="space-y-6">
              <!-- Photo Upload Section -->
              <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">Member Photo</h4>
                
                <div class="flex flex-col items-center">
                  <div class="relative mb-4">
                    <div v-if="photoPreview || member.photo_path" class="relative">
                      <img
                        :src="getPhotoUrl()"
                        alt="Member photo"
                        class="w-48 h-48 object-cover rounded-lg border-4 border-green-500"
                        @error="handleImageError"
                      />
                      <button
                        type="button"
                        @click="removePhoto"
                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600 shadow-lg"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </button>
                    </div>
                    <div
                      v-else
                      class="w-48 h-48 border-4 border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center bg-gray-50"
                    >
                      <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                      <span class="text-sm text-gray-500">No photo</span>
                    </div>
                  </div>

                  <div class="flex gap-4">
                    <button
                      type="button"
                      @click="$refs.fileInput.click()"
                      class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                      </svg>
                      {{ member.photo_path ? 'Change Photo' : 'Upload Photo' }}
                    </button>
                    <button
                      type="button"
                      @click="openCamera"
                      class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition-colors"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      Take Photo
                    </button>
                  </div>

                  <input
                    ref="fileInput"
                    type="file"
                    accept="image/*"
                    @change="handleFileUpload"
                    class="hidden"
                  />
                </div>
              </div>

              <!-- Camera Modal -->
              <div
                v-if="showCamera"
                class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-[60] p-4"
                @click="closeCamera"
              >
                <div class="bg-white rounded-lg p-6 max-w-2xl w-full" @click.stop>
                  <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Take Photo</h3>
                    <button
                      type="button"
                      @click="closeCamera"
                      class="text-gray-500 hover:text-gray-700"
                    >
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>

                  <div class="relative bg-black rounded-lg overflow-hidden">
                    <video
                      ref="videoRef"
                      autoplay
                      playsinline
                      class="w-full h-auto"
                    ></video>
                    <canvas ref="canvasRef" class="hidden"></canvas>
                  </div>

                  <div class="mt-4 flex justify-center">
                    <button
                      type="button"
                      @click="capturePhoto"
                      class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg font-semibold transition-colors"
                    >
                      📸 Capture Photo
                    </button>
                  </div>
                </div>
              </div>

              <!-- Connection Status Banner -->
              <div :class="[
                'p-4 rounded-lg flex items-center gap-3',
                member.connection_status === 1 ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'
              ]">
                <div :class="[
                  'w-4 h-4 rounded-full',
                  member.connection_status === 1 ? 'bg-green-500' : 'bg-red-500'
                ]"></div>
                <span :class="[
                  'font-semibold',
                  member.connection_status === 1 ? 'text-green-800' : 'text-red-800'
                ]">
                  {{ member.connection_status === 1 ? 'Connected' : 'Disconnected' }}
                </span>
              </div>

              <!-- Personal Information -->
              <div>
                <h4 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Personal Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Account No *</label>
                    <input
                      v-model="member.account_no"
                      type="text"
                      required
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Meter No *</label>
                    <input
                      v-model="member.meter_no"
                      type="text"
                      required
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                    <input
                      v-model="member.fname"
                      type="text"
                      required
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                    <input
                      v-model="member.mname"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                    <input
                      v-model="member.lname"
                      type="text"
                      required
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Suffix</label>
                    <input
                      v-model="member.suffix"
                      type="text"
                      placeholder="Jr., Sr., III"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth *</label>
                    <input
                      v-model="member.date_of_birth"
                      type="date"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Place of Birth</label>
                    <input
                      v-model="member.place_of_birth"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sex *</label>
                    <select
                      v-model="member.sex"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                      :class="{ 'border-red-500': !member.sex }"
                    >
                      <!-- FIXED: Shows "Select Sex" only if no value exists, otherwise shows the current value -->
                      <option value="">Select Sex</option>
                      <option value="Male">Male</option>
                      <option value="Female">Female</option>
                    </select>
                    <p v-if="!member.sex" class="text-red-500 text-xs mt-1">Sex is required</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Civil Status</label>
                    <select
                      v-model="member.civil_status"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    >
                      <option value="">Select</option>
                      <option value="Single">Single</option>
                      <option value="Married">Married</option>
                      <option value="Widowed">Widowed</option>
                      <option value="Separated">Separated</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mobile No</label>
                    <input
                      v-model="member.mobile_no"
                      type="text"
                      placeholder="09XX XXX XXXX"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Religion</label>
                    <input
                      v-model="member.religion"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ethnicity</label>
                    <input
                      v-model="member.ethnicity"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Language</label>
                    <input
                      v-model="member.language"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Height (feet)</label>
                    <input
                      v-model.number="member.height"
                      type="number"
                      step="0.01"
                      placeholder="Enter height in feet"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Weight (kg)</label>
                    <input
                      v-model.number="member.weight"
                      type="number"
                      step="0.01"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Occupation</label>
                    <input
                      v-model="member.occupation"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Company Address</label>
                    <input
                      v-model="member.company_address"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                </div>
              </div>

              <!-- Education Information -->
              <div>
                <h4 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Education Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Education Attainment</label>
                    <select
                      v-model="member.education_attainment"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    >
                      <option value="">Select</option>
                      <option value="Elementary">Elementary</option>
                      <option value="High School">High School</option>
                      <option value="Vocational">Vocational</option>
                      <option value="College">College</option>
                      <option value="Graduate">Graduate</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Year Graduated</label>
                    <input
                      v-model="member.year_graduated"
                      type="text"
                      placeholder="YYYY"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Course</label>
                    <input
                      v-model="member.course"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">School Address</label>
                    <input
                      v-model="member.school_address"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                </div>
              </div>

              <!-- Spouse Information -->
              <div>
                <h4 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Spouse Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Spouse First Name</label>
                    <input
                      v-model="member.spouse_fname"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Spouse Middle Name</label>
                    <input
                      v-model="member.spouse_mname"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Spouse Last Name</label>
                    <input
                      v-model="member.spouse_lname"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Spouse Suffix</label>
                    <input
                      v-model="member.spouse_suffix"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Spouse Date of Birth</label>
                    <input
                      v-model="member.spouse_date_of_birth"
                      type="date"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Spouse Phone No</label>
                    <input
                      v-model="member.spouse_phone_no"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Spouse Ethnicity</label>
                    <input
                      v-model="member.spouse_ethnicity"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Spouse Occupation</label>
                    <input
                      v-model="member.spouse_occupation"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Spouse Address</label>
                    <input
                      v-model="member.spouse_address"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                </div>
              </div>

              <!-- Government ID Information -->
              <div>
                <h4 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Government ID Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Government ID Type</label>
                    <select
                      v-model="member.government_type_id"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    >
                      <option value="">Select ID Type</option>
                      <option value="1">SSS</option>
                      <option value="2">GSIS</option>
                      <option value="3">PhilHealth</option>
                      <option value="4">Pag-IBIG</option>
                      <option value="5">Driver's License</option>
                      <option value="6">Passport</option>
                      <option value="7">Voter's ID</option>
                      <option value="8">Senior Citizen ID</option>
                      <option value="9">PWD ID</option>
                      <option value="10">Postal ID</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Government ID Number</label>
                    <input
                      v-model="member.government_no"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                </div>
              </div>

              <!-- Address Information -->
              <div>
                <h4 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Address Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Purok *</label>
                    <select
                      v-model="member.purok_id"
                      required
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    >
                      <option value="">Select Purok</option>
                      <option v-for="purok in puroks" :key="purok.purok_id" :value="purok.purok_id">
                        {{ purok.purok }}
                      </option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">TS Number *</label>
                    <select
                      v-model="member.ts_Id"
                      required
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    >
                      <option value="">Select TS Number</option>
                      <option v-for="ts in tsNumbers" :key="ts.ts_Id" :value="ts.ts_Id">
                        {{ ts.ts_no }} {{ ts.landmark ? '- ' + ts.landmark : '' }}
                      </option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Barangay *</label>
                    <input
                      v-model="member.barangay"
                      type="text"
                      required
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Municipality *</label>
                    <input
                      v-model="member.municipality"
                      type="text"
                      required
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Province *</label>
                    <input
                      v-model="member.province"
                      type="text"
                      required
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Region</label>
                    <input
                      v-model="member.region"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Zip Code</label>
                    <input
                      v-model="member.zip_code"
                      type="text"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                </div>
              </div>

              <!-- Membership Fee -->
              <div>
                <h4 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Membership Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Membership Fee *</label>
                    <select
                      v-model="member.membership_fee_id"
                      required
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    >
                      <option value="">Select Membership Fee</option>
                      <option v-for="fee in membershipFees" :key="fee.membership_fee_id" :value="fee.membership_fee_id">
                        ₱{{ fee.fee_amount.toFixed(2) }} {{ fee.description ? '- ' + fee.description : '' }}
                      </option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Previous Balance</label>
                    <input
                      v-model.number="member.prev_balance"
                      type="number"
                      step="0.01"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    />
                  </div>
                </div>
              </div>

              <div v-if="saveError" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                {{ saveError }}
              </div>

              <div v-if="saveSuccess" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                {{ saveSuccess }}
              </div>

              <div class="flex gap-4 pt-4 border-t">
                <button
                  type="submit"
                  :disabled="saving"
                  class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
                >
                  {{ saving ? 'Updating...' : 'Update Member' }}
                </button>
                <button
                  type="button"
                  @click="navigateTo('/president/members')"
                  :disabled="saving"
                  class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-6 rounded-lg transition-colors disabled:bg-gray-200"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth'
})

import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useRoute } from '#app'
import { useApi } from '~/composables/useApi'

const authStore = useAuthStore()
const route = useRoute()
const api = useApi()

const isClient = ref(false)
const member = ref({})
const loading = ref(true)
const loadError = ref('')
const saving = ref(false)
const saveError = ref('')
const saveSuccess = ref('')
const puroks = ref([])
const tsNumbers = ref([])
const membershipFees = ref([])
const imageError = ref(false)

// Photo handling refs
const photoPreview = ref(null)
const showCamera = ref(false)
const cameraStream = ref(null)
const fileInputRef = ref(null)
const videoRef = ref(null)
const canvasRef = ref(null)
const photoFile = ref(null)
const photoChanged = ref(false)

const memberId = computed(() => route.params.id)
const currentPath = computed(() => route.path)

const tabs = [
  { label: 'Members', path: '/president/members' },
  { label: 'Expenses', path: '/president/expenses' },
  { label: 'Master-list', path: '/president/master-list' },
  { label: 'Collection', path: '/president/collection' },
  { label: 'Payment Status', path: '/president/payment-status' },
  { label: 'Important Info', path: '/president/important-info' },
  { label: 'Employee', path: '/president/employee' }
]

const getPhotoUrl = () => {
  if (photoPreview.value) {
    return photoPreview.value
  }
  
  if (member.value.photo_path && !imageError.value) {
    return member.value.photo_path
  }
  
  return null
}

const handleImageError = () => {
  console.error('Failed to load image:', member.value.photo_path)
  imageError.value = true
}

const handleFileUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    photoFile.value = file
    photoChanged.value = true
    imageError.value = false
    const reader = new FileReader()
    reader.onloadend = () => {
      photoPreview.value = reader.result
    }
    reader.readAsDataURL(file)
  }
}

const openCamera = async () => {
  try {
    if (cameraStream.value) {
      cameraStream.value.getTracks().forEach(t => t.stop())
    }

    showCamera.value = true

    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'user' },
      audio: false
    })

    videoRef.value.srcObject = stream
    cameraStream.value = stream

    await videoRef.value.play().catch(err => {
      console.warn('Video play failed initially:', err)
    })

  } catch (err) {
    if (err.name === 'AbortError') {
      console.warn('Retrying camera start...')
      setTimeout(openCamera, 1000)
    } else {
      alert('Unable to access camera. Please check permissions.')
      console.error('Camera error:', err)
    }
  }
}

const capturePhoto = () => {
  if (videoRef.value && canvasRef.value) {
    const video = videoRef.value
    const canvas = canvasRef.value
    const context = canvas.getContext('2d')
    
    canvas.width = video.videoWidth
    canvas.height = video.videoHeight
    context.drawImage(video, 0, 0, canvas.width, canvas.height)
    
    canvas.toBlob((blob) => {
      photoFile.value = new File([blob], 'camera-photo.jpg', { type: 'image/jpeg' })
      photoPreview.value = canvas.toDataURL('image/jpeg')
      photoChanged.value = true
      imageError.value = false
      closeCamera()
    }, 'image/jpeg', 0.95)
  }
}

const closeCamera = () => {
  if (cameraStream.value) {
    cameraStream.value.getTracks().forEach(track => track.stop())
    cameraStream.value = null
  }
  showCamera.value = false
}

const removePhoto = () => {
  photoPreview.value = null
  photoFile.value = null
  photoChanged.value = true
  imageError.value = false
  member.value.photo_path = null
  member.value.photo_name = null
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

const loadMember = async () => {
  loading.value = true
  loadError.value = ''
  imageError.value = false
  try {
    const data = await api(`/members/${memberId.value}`)
    member.value = data.member
    
    // Format dates for input fields
    if (member.value.date_of_birth) {
      member.value.date_of_birth = member.value.date_of_birth.split('T')[0]
    }
    if (member.value.spouse_date_of_birth) {
      member.value.spouse_date_of_birth = member.value.spouse_date_of_birth.split('T')[0]
    }
      // Ensure sex has a valid value (either Male, Female, or empty string for user to select)
    if (!member.value.sex || (member.value.sex !== 'Male' && member.value.sex !== 'Female')) {
      // If sex value is invalid or missing, leave it empty for user to select
      member.value.sex = ''
      console.warn('Sex field was invalid or missing, user must select a value')
    }
  } catch (err) {
    console.error('Failed to load member:', err)
    loadError.value = err.message || 'Failed to load member data'
  } finally {
    loading.value = false
  }
}

const loadPuroks = async () => {
  try {
    puroks.value = await api('/puroks')
  } catch (err) {
    console.error('Failed to load puroks:', err)
  }
}

const loadTsNumbers = async () => {
  try {
    tsNumbers.value = await api('/ts-numbers')
  } catch (err) {
    console.error('Failed to load TS numbers:', err)
  }
}

const loadMembershipFees = async () => {
  try {
    membershipFees.value = await api('/membership-fees')
  } catch (err) {
    console.error('Failed to load membership fees:', err)
  }
}

const updateMember = async () => {
  saving.value = true
  saveError.value = ''
  saveSuccess.value = ''
  
  try {
    // Validate required fields first
    if (!member.value.sex || !['Male', 'Female'].includes(member.value.sex)) {
      saveError.value = 'Sex field is required and must be either "Male" or "Female"'
      saving.value = false
      return
    }

    if (!member.value.fname || member.value.fname.trim() === '') {
      saveError.value = 'First name is required'
      saving.value = false
      return
    }

    if (!member.value.lname || member.value.lname.trim() === '') {
      saveError.value = 'Last name is required'
      saving.value = false
      return
    }
    
    // Clean up member data
    const cleanedMember = { ...member.value }
    
    // Remove nested objects that shouldn't be sent
    delete cleanedMember.purok
    delete cleanedMember.ts_number
    delete cleanedMember.membership_fee
    delete cleanedMember.water_consumptions

    // Define numeric fields that need proper type conversion
    const numericFields = ['height', 'weight', 'prev_balance', 'purok_id', 'ts_Id', 'membership_fee_id']
    
    // Convert empty strings to null for optional fields, except required ones
    const requiredFields = ['account_no', 'meter_no', 'fname', 'lname', 'barangay', 
                            'municipality', 'province', 'sex', 
                            'purok_id', 'ts_Id', 'membership_fee_id']
    
    Object.keys(cleanedMember).forEach(key => {
      const value = cleanedMember[key]
      
      // Skip if it's a required field
      if (requiredFields.includes(key)) {
        // Just ensure it's not empty
        if (value === '' || value === null || value === undefined) {
          if (key === 'sex') {
            saveError.value = 'Sex is required'
            throw new Error('Sex is required')
          }
        }
        return
      }
      
      // Convert empty strings to null for optional fields
      if (value === '' || value === '[object Object]') {
        cleanedMember[key] = null
      } 
      // Handle numeric fields
      else if (numericFields.includes(key) && value !== null && value !== '') {
        const numValue = Number(value)
        cleanedMember[key] = isNaN(numValue) ? null : numValue
      }
    })

    // Ensure date fields are properly formatted or null
    const dateFields = ['date_of_birth', 'spouse_date_of_birth']
    dateFields.forEach(field => {
      if (cleanedMember[field]) {
        const dateRegex = /^\d{4}-\d{2}-\d{2}$/
        if (!dateRegex.test(cleanedMember[field])) {
          console.warn(`Invalid date format for ${field}:`, cleanedMember[field])
          cleanedMember[field] = null
        }
      }
    })

    console.log('Cleaned member data to send:', cleanedMember)

    // If photo was changed, use FormData, otherwise use regular JSON
    if (photoChanged.value) {
      const formData = new FormData()
      
      // Add all member data
      Object.keys(cleanedMember).forEach(key => {
        if (key !== 'photo_path' && key !== 'photo_name') {
          const value = cleanedMember[key]
          
          // Only append non-null values
          if (value !== null && value !== undefined) {
            formData.append(key, value.toString())
          }
        }
      })
      
      // Add photo if exists
      if (photoFile.value) {
        formData.append('photo', photoFile.value)
      } else if (!member.value.photo_path) {
        // Photo was removed
        formData.append('remove_photo', '1')
      }
      
      // Add PUT method override for FormData
      formData.append('_method', 'PUT')
      
      await api(`/members/${memberId.value}`, {
        method: 'POST',
        body: formData
      })
    } else {
      // No photo change, use regular JSON update
      await api(`/members/${memberId.value}`, {
        method: 'PUT',
        body: cleanedMember
      })
    }

    // Store success message in sessionStorage
    sessionStorage.setItem('memberUpdateSuccess', 'Member updated successfully!')
    
    // Redirect to members list
    navigateTo('/president/members')
    
  } catch (err) {
    console.error('Full error object:', err)
    
    // Handle validation errors specifically
    if (err.status === 422 || err.statusCode === 422) {
      console.error('422 Validation Error Details:', err.data)
      
      // Extract validation messages
      if (err.data && err.data.errors) {
        const errorMessages = Object.values(err.data.errors).flat()
        saveError.value = `Validation errors: ${errorMessages.join(', ')}`
      } else if (err.data && err.data.message) {
        saveError.value = `Validation error: ${err.data.message}`
      } else {
        saveError.value = 'Validation failed. Please check all fields.'
      }
    } else {
      saveError.value = err.message || 'Failed to update member. Please try again.'
    }
    
    // Log all error details for debugging
    console.error('Error status:', err.status || err.statusCode)
    console.error('Error data:', err.data)
    console.error('Error message:', err.message)
  } finally {
    saving.value = false
  }
}

const handleLogout = async () => {
  await authStore.logout()
  navigateTo('/')
}

onMounted(() => {
  isClient.value = true
  authStore.initAuth()
  loadMember()
  loadPuroks()
  loadTsNumbers()
  loadMembershipFees()
})
</script>