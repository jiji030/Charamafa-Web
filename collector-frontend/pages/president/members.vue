<template>
  <!-- Only render after client mount -->
  <div v-if="isClient">
    <!-- Show loading placeholder if no user yet -->
    <div v-if="!authStore.user" class="min-h-screen bg-gray-100 flex items-center justify-center">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-700 mx-auto mb-4"></div>
        <p class="text-gray-600">Loading...</p>
      </div>
    </div>

    <!-- Actual dashboard -->
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
                currentPath === tab.path
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
        <!-- Success Notification Toast -->
        <div
          v-if="showNotification"
          class="fixed top-20 right-4 z-50 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center gap-3 animate-slide-in"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span class="font-medium">{{ notificationMessage }}</span>
          <button @click="showNotification = false" class="ml-4 hover:bg-green-600 rounded p-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
          <div class="p-6 border-b flex justify-between items-center">
            <div>
              <h2 class="text-xl font-bold text-gray-800">Members' List</h2>
              <div class="flex items-center gap-4 mt-2">
                <div class="bg-green-100 px-4 py-2 rounded-lg">
                  <span class="text-2xl font-bold text-green-700">{{ totalMembers }}</span>
                  <p class="text-sm text-gray-600">Total Members</p>
                </div>
              </div>
            </div>
            <div class="flex gap-4 items-center">
              <button
                @click="showAddModal = true"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Member
              </button>
              <div class="relative">
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search Name / Account No"
                  class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 w-64"
                />
                <svg
                  class="w-5 h-5 text-gray-400 absolute left-3 top-2.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                  />
                </svg>
              </div>
            </div>
          </div>

          <!-- Top scrollbar -->
          <div 
            ref="topScrollbar"
            class="overflow-x-auto mb-0"
            @scroll="syncScroll('top')"
          >
            <div :style="{ width: scrollWidth + 'px', height: '1px' }"></div>
          </div>          <!-- Main table with bottom scrollbar -->
          <div 
            ref="bottomScrollbar"
            class="overflow-x-auto"
            @scroll="syncScroll('bottom')"
          >
            <table class="w-full text-sm">
              <thead class="bg-green-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase whitespace-nowrap">Status</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase whitespace-nowrap">Payment</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase whitespace-nowrap">Account No.</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase whitespace-nowrap">Meter No.</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase whitespace-nowrap">Name</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase whitespace-nowrap">Purok</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase whitespace-nowrap">TS Number</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase whitespace-nowrap sticky right-0 bg-green-50">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                <tr
                  v-for="member in filteredMembers"
                  :key="member.member_id"
                  class="hover:bg-gray-50 cursor-pointer"
                  @click="viewMemberDetails(member)"
                >
                  <td class="px-4 py-3 whitespace-nowrap">
                    <span
                      :class="[
                        'px-2 py-1 text-xs font-medium rounded-full',
                        member.connection_status === 1 
                          ? 'bg-green-100 text-green-800'
                          : 'bg-red-100 text-red-800'
                      ]"
                    >
                      {{ member.connection_status === 1 ? 'Connected' : 'Disconnected' }}
                    </span>
                  </td>                  
                  <td class="px-4 py-3 whitespace-nowrap">
                    <span
                      :class="[
                        'px-2 py-1 text-xs font-medium rounded-full',
                        member.is_paid 
                          ? 'bg-green-100 text-green-800'
                          : 'bg-yellow-100 text-yellow-800'
                      ]"
                      :title="`is_paid value: ${member.is_paid} (${typeof member.is_paid})`"
                    >
                      {{ member.is_paid ? 'Paid' : 'Unpaid' }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-gray-900 whitespace-nowrap font-medium">{{ member.account_no }}</td>
                  <td class="px-4 py-3 text-gray-900 whitespace-nowrap">{{ member.meter_no }}</td>
                  <td class="px-4 py-3 text-gray-900 whitespace-nowrap">
                    {{ member.fname }} {{ member.mname ? member.mname.charAt(0) + '.' : '' }} {{ member.lname }} {{ member.suffix || '' }}
                  </td>
                  <td class="px-4 py-3 text-gray-900 whitespace-nowrap">{{ member.purok?.purok || '-' }}</td>
                  <td class="px-4 py-3 text-gray-900 whitespace-nowrap">{{ member.tsNumber?.ts_no || member.ts_number?.ts_no || '-' }}</td>
                  <td class="px-4 py-3 whitespace-nowrap sticky right-0 bg-white" @click.stop>
                    <div class="flex gap-1">
                      <button
                        @click="resetWaterConsumption(member)"
                        class="bg-orange-500 hover:bg-orange-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                        title="Reset Water Consumption"
                      >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                      </button>
                      <button
                        @click="viewPaymentHistory(member.member_id)"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                        title="View Payment History"
                      >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                      </button>
                      <button
                        @click="viewMember(member.member_id)"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                        title="Edit Member"
                      >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                      <button
                        @click="deleteMember(member.member_id)"
                        class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs flex items-center justify-center"
                        title="Delete Member"
                      >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="loading" class="text-center py-12">
            <p class="text-gray-600">Loading members...</p>
          </div>

          <div v-if="!loading && filteredMembers.length === 0" class="text-center py-12">
            <p class="text-gray-600">No members found</p>
          </div>
        </div>
      </main>
    </div>

    <!-- Add Member Modal -->
    <div
      v-if="showAddModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto"
      @click="closeAddModal"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full my-8 max-h-[90vh] overflow-y-auto" @click.stop>
        <div class="p-6 border-b sticky top-0 bg-white z-10">
          <div class="flex justify-between items-center">
            <h3 class="text-2xl font-bold text-gray-800">Add New Member</h3>
            <button @click="closeAddModal" class="text-gray-500 hover:text-gray-700">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Photo Upload Section - Place this right after the modal header -->
        <!-- Photo Upload Section - Place this right after the modal header -->
        <div class="p-6">
          <form @submit.prevent="saveMember" class="space-y-6">
            <!-- Photo Upload Section -->
            <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
              <h4 class="text-lg font-semibold text-gray-800 mb-4">Member Photo</h4>
              
              <div class="flex flex-col items-center">
                <!-- Photo Preview -->
                <div class="relative mb-4">
                  <div v-if="photoPreview" class="relative">
                    <img
                      :src="photoPreview"
                      alt="Member photo"
                      class="w-48 h-48 object-cover rounded-lg border-4 border-green-500"
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
                    <span class="text-sm text-gray-500">No photo yet</span>
                  </div>
                </div>

                <!-- Upload Options - Always Visible -->
                <div class="flex gap-4">
                  <button
                    type="button"
                    @click="$refs.fileInput.click()"
                    class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    Upload Photo
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

                <!-- Hidden File Input -->
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
              <!-- personal information -->
            <div>
              <h4 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Personal Information</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Account No <span class="text-red-500">*</span></label>
                  <div class="relative">
                    <input
                      v-model="newMember.account_no"
                      @input="checkAccountNumber"
                      type="text"
                      required
                      :class="[
                        'w-full px-4 py-2 border rounded-lg focus:ring-2',
                        accountNoExists ? 
                          'border-red-300 focus:ring-red-500 focus:border-red-500' : 
                          'border-gray-300 focus:ring-green-500 focus:border-green-500'
                      ]"
                      placeholder="Enter unique account number"
                    />
                    
                    <!-- Loading indicator -->
                    <div v-if="checkingAccountNo" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                      <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                    </div>
                    
                    <!-- Success indicator -->
                    <div v-else-if="newMember.account_no && !accountNoExists && !accountNoError" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                      <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                      </svg>
                    </div>
                    
                    <!-- Error indicator -->
                    <div v-else-if="accountNoExists" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                      <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                      </svg>
                    </div>
                  </div>
                  
                  <!-- Validation Messages -->
                  <div v-if="accountNoError" class="mt-2 p-2 bg-red-50 border border-red-200 rounded-md">
                    <p class="text-red-600 text-sm">⚠️ {{ accountNoError }}</p>
                  </div>
                  <div v-else-if="newMember.account_no && !accountNoExists && !checkingAccountNo" class="mt-2 p-2 bg-green-50 border border-green-200 rounded-md">
                    <p class="text-green-600 text-sm">✅ Account number is available</p>
                  </div>
                </div><div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Meter No <span class="text-red-500">*</span></label>
                  <input
                    v-model="newMember.meter_no"
                    @input="checkSharedMeter"
                    type="text"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                  
                  <!-- Shared Meter Notification -->
                  <div v-if="sharedMeterInfo && sharedMeterInfo.length > 0" class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-blue-800 font-medium">🔗 Shared Meter Detected!</p>
                    <p class="text-blue-600 text-sm mt-1">This meter number is already used by {{ sharedMeterInfo.length }} member(s):</p>
                    <ul class="text-blue-600 text-sm mt-2 ml-4 list-disc">
                      <li v-for="member in sharedMeterInfo" :key="member.member_id">
                        {{ member.account_no }} - {{ member.fname }} {{ member.lname }}
                      </li>
                    </ul>
                    <p class="text-blue-600 text-xs mt-2">
                      <strong>Note:</strong> Water consumption records and payment status will be automatically copied from the existing members.
                    </p>
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">First Name <span class="text-red-500">*</span></label>
                  <input
                    v-model="newMember.fname"
                    type="text"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                  <input
                    v-model="newMember.mname"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Last Name <span class="text-red-500">*</span></label>
                  <input
                    v-model="newMember.lname"
                    type="text"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Suffix</label>
                  <input
                    v-model="newMember.suffix"
                    type="text"
                    placeholder="Jr., Sr., III"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                  <input
                    v-model="newMember.date_of_birth"
                    type="date"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Place of Birth</label>
                  <input
                    v-model="newMember.place_of_birth"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Sex <span class="text-red-500">*</span></label>
                  <select
                    v-model="newMember.sex"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  >
                    <option value="">Select</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Civil Status</label>
                  <select
                    v-model="newMember.civil_status"
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
                    v-model="newMember.mobile_no"
                    type="text"
                    placeholder="09XX XXX XXXX"
                    maxlength="11"
                    pattern="^09\d{9}$"
                    title="Please enter a valid 11-digit mobile number starting with '09'"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                    @input="validateMobileNumber"
                  />
                  <p v-if="mobileError" class="mt-1 text-sm text-red-600">{{ mobileError }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Religion</label>
                  <input
                    v-model="newMember.religion"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Ethnicity</label>
                  <input
                    v-model="newMember.ethnicity"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Language</label>
                  <input
                    v-model="newMember.language"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Height (feet (put . instead of '))</label>
                  <input
                    v-model.number="newMember.height"
                    type="number"
                    step="0.01"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Weight (kg)</label>
                  <input
                    v-model.number="newMember.weight"
                    type="number"
                    step="0.01"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Occupation</label>
                  <input
                    v-model="newMember.occupation"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Company Address</label>
                  <input
                    v-model="newMember.company_address"
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
                    v-model="newMember.education_attainment"
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
                    v-model="newMember.year_graduated"
                    type="text"
                    placeholder="YYYY"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Course</label>
                  <input
                    v-model="newMember.course"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">School Address</label>
                  <input
                    v-model="newMember.school_address"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
              </div>
            </div>

            <!-- Spouse Information -->
            <div>
              <h4 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Spouse Information (if applicable)</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Spouse First Name</label>
                  <input
                    v-model="newMember.spouse_fname"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Spouse Middle Name</label>
                  <input
                    v-model="newMember.spouse_mname"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Spouse Last Name</label>
                  <input
                    v-model="newMember.spouse_lname"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Spouse Suffix</label>
                  <input
                    v-model="newMember.spouse_suffix"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Spouse Date of Birth</label>
                  <input
                    v-model="newMember.spouse_date_of_birth"
                    type="date"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Spouse Phone No</label>
                  <input
                    v-model="newMember.spouse_phone_no"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Spouse Ethnicity</label>
                  <input
                    v-model="newMember.spouse_ethnicity"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Spouse Occupation</label>
                  <input
                    v-model="newMember.spouse_occupation"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Spouse Address</label>
                  <input
                    v-model="newMember.spouse_address"
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
                    v-model="newMember.government_type_id"
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
                    v-model="newMember.government_no"
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
                  <label class="block text-sm font-medium text-gray-700 mb-2">Purok <span class="text-red-500">*</span></label>
                  <select
                    v-model="newMember.purok_id"
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
                  <label class="block text-sm font-medium text-gray-700 mb-2">TS Number <span class="text-red-500">*</span></label>
                  <select
                    v-model="newMember.ts_Id"
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
                  <label class="block text-sm font-medium text-gray-700 mb-2">Barangay <span class="text-red-500">*</span></label>
                  <input
                    v-model="newMember.barangay"
                    type="text"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Municipality <span class="text-red-500">*</span></label>
                  <input
                    v-model="newMember.municipality"
                    type="text"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Province <span class="text-red-500">*</span></label>
                  <input
                    v-model="newMember.province"
                    type="text"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Region</label>
                  <input
                    v-model="newMember.region"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Zip Code</label>
                  <input
                    v-model="newMember.zip_code"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                  />
                </div>
              </div>
            </div>

            <!-- Membership Agreement -->
            <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
              <h4 class="text-lg font-semibold text-gray-800 mb-4">Membership Agreement</h4>
              <div class="prose prose-sm max-w-none text-gray-700 mb-4 space-y-4">
                <p class="leading-relaxed">
                  I <strong class="text-blue-700">{{ fullName }}</strong> hereby apply for membership in the 
                  <strong>CHARMAFA - A WATERWORKS SERVICE ASSOCIATION</strong>, and pledge to abide by its Constitution, 
                  By-Laws, and such rules and regulations as may be adopted by the Association.
                </p>
                <p class="leading-relaxed">
                  The I hereby further pledge, based on my heart fully support without mental reservation to Charito Mahayag 
                  Farmer's Association (CHARMAFA), to give the membership in three (3) categories for the amount of:
                </p>
                
                <div class="bg-white p-4 rounded border border-blue-300 space-y-3">
                  <p class="font-semibold text-gray-800 mb-3">Select Membership Category:</p>
                  <label 
                    v-for="fee in membershipFees" 
                    :key="fee.membership_fee_id"
                    class="flex items-center gap-3 p-3 border rounded hover:bg-blue-50 cursor-pointer"
                  >
                    <input
                      v-model="selectedMembershipFee"
                      type="radio"
                      name="membership"
                      :value="fee.membership_fee_id.toString()"
                      required
                      class="w-4 h-4 text-green-600 focus:ring-green-500"
                    />
                    <span class="font-medium">₱{{ fee.fee_amount.toFixed(2) }}</span>
                    <span v-if="fee.description" class="text-sm text-gray-600">- {{ fee.description }}</span>
                  </label>
                </div>

                <p class="leading-relaxed">
                  And the Said amount shall be remitted to the Association Treasurer.
                </p>

                <div class="flex items-start gap-3 mt-4 bg-white p-4 rounded border border-blue-300">
                  <input
                    v-model="agreedToTerms"
                    type="checkbox"
                    id="agreement"
                    required
                    class="mt-1 w-4 h-4 text-green-600 focus:ring-green-500"
                  />
                  <label for="agreement" class="text-sm font-medium text-gray-800">
                    I agree to the terms and conditions stated above and confirm that all information provided is accurate and complete.
                  </label>
                </div>
              </div>
            </div>

            <div v-if="saveError" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
              {{ saveError }}
            </div>

            <div class="flex gap-4 sticky bottom-0 bg-white pt-4 border-t">              <button
                type="submit"
                :disabled="saving || !agreedToTerms || accountNoExists || checkingAccountNo"
                class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
              >
                {{ saving ? 'Saving...' : 'Save Member' }}
              </button>
              <button
                type="button"
                @click="closeAddModal"
                :disabled="saving"
                class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-6 rounded-lg transition-colors disabled:bg-gray-200"
              >
                Cancel
              </button>
            </div>
          </form>        </div>
      </div>
    </div>

    <!-- Member Details Modal -->
    <div
      v-if="showMemberDetailsModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click="closeMemberDetailsModal"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
        <div class="sticky top-0 bg-white border-b p-6">
          <div class="flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">Member Details</h3>
            <button
              @click="closeMemberDetailsModal"
              class="text-gray-500 hover:text-gray-700"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div v-if="selectedMemberDetails" class="p-6">
          <!-- Basic Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Photo Section -->
            <div class="flex flex-col items-center">
              <div class="w-48 h-48 border-2 border-gray-200 rounded-lg overflow-hidden mb-4">
                <img
                  v-if="selectedMemberDetails.photo_path"
                  :src="selectedMemberDetails.photo_path"
                  :alt="selectedMemberDetails.fname + ' ' + selectedMemberDetails.lname"
                  class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full bg-gray-100 flex items-center justify-center">
                  <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                </div>
              </div>
              <!-- Status Badges -->
              <div class="flex gap-2 mb-4">
                <span
                  :class="[
                    'px-3 py-1 text-sm font-medium rounded-full',
                    selectedMemberDetails.connection_status === 1 
                      ? 'bg-green-100 text-green-800'
                      : 'bg-red-100 text-red-800'
                  ]"
                >
                  {{ selectedMemberDetails.connection_status === 1 ? 'Connected' : 'Disconnected' }}
                </span>
                <span
                  :class="[
                    'px-3 py-1 text-sm font-medium rounded-full',
                    selectedMemberDetails.is_paid 
                      ? 'bg-green-100 text-green-800'
                      : 'bg-yellow-100 text-yellow-800'
                  ]"
                >
                  {{ selectedMemberDetails.is_paid ? 'Paid' : 'Unpaid' }}
                </span>
              </div>
            </div>

            <!-- Basic Info -->
            <div class="space-y-4">
              <div>
                <h4 class="font-semibold text-gray-800 mb-3">Basic Information</h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                  <div>
                    <span class="text-gray-600">Account No:</span>
                    <p class="font-medium">{{ selectedMemberDetails.account_no }}</p>
                  </div>
                  <div>
                    <span class="text-gray-600">Meter No:</span>
                    <p class="font-medium">{{ selectedMemberDetails.meter_no }}</p>
                  </div>
                  <div class="col-span-2">
                    <span class="text-gray-600">Full Name:</span>
                    <p class="font-medium">
                      {{ selectedMemberDetails.fname }} 
                      {{ selectedMemberDetails.mname ? selectedMemberDetails.mname + ' ' : '' }}
                      {{ selectedMemberDetails.lname }} 
                      {{ selectedMemberDetails.suffix || '' }}
                    </p>
                  </div>
                  <div>
                    <span class="text-gray-600">Date of Birth:</span>
                    <p class="font-medium">{{ formatDate(selectedMemberDetails.date_of_birth) }}</p>
                  </div>
                  <div>
                    <span class="text-gray-600">Sex:</span>
                    <p class="font-medium">{{ selectedMemberDetails.sex }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Detailed Information in Tabs or Sections -->
          <div class="space-y-6">
            <!-- Contact & Personal Info -->
            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="font-semibold text-gray-800 mb-3">Contact & Personal Information</h4>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                  <span class="text-gray-600">Mobile No:</span>
                  <p class="font-medium">{{ selectedMemberDetails.mobile_no || '-' }}</p>
                </div>
                <div>
                  <span class="text-gray-600">Civil Status:</span>
                  <p class="font-medium">{{ selectedMemberDetails.civil_status || '-' }}</p>
                </div>
                <div>
                  <span class="text-gray-600">Religion:</span>
                  <p class="font-medium">{{ selectedMemberDetails.religion || '-' }}</p>
                </div>
                <div>
                  <span class="text-gray-600">Ethnicity:</span>
                  <p class="font-medium">{{ selectedMemberDetails.ethnicity || '-' }}</p>
                </div>
                <div>
                  <span class="text-gray-600">Language:</span>
                  <p class="font-medium">{{ selectedMemberDetails.language || '-' }}</p>
                </div>
                <div>
                  <span class="text-gray-600">Occupation:</span>
                  <p class="font-medium">{{ selectedMemberDetails.occupation || '-' }}</p>
                </div>
              </div>
            </div>

            <!-- Address Information -->
            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="font-semibold text-gray-800 mb-3">Address Information</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                  <span class="text-gray-600">Purok:</span>
                  <p class="font-medium">{{ selectedMemberDetails.purok?.purok || '-' }}</p>
                </div>
                <div>
                  <span class="text-gray-600">TS Number:</span>
                  <p class="font-medium">{{ selectedMemberDetails.tsNumber?.ts_no || selectedMemberDetails.ts_number?.ts_no || '-' }}</p>
                </div>
                <div>
                  <span class="text-gray-600">Barangay:</span>
                  <p class="font-medium">{{ selectedMemberDetails.barangay }}</p>
                </div>
                <div>
                  <span class="text-gray-600">Municipality:</span>
                  <p class="font-medium">{{ selectedMemberDetails.municipality }}</p>
                </div>
                <div>
                  <span class="text-gray-600">Province:</span>
                  <p class="font-medium">{{ selectedMemberDetails.province }}</p>
                </div>
                <div>
                  <span class="text-gray-600">Region:</span>
                  <p class="font-medium">{{ selectedMemberDetails.region || '-' }}</p>
                </div>
              </div>
            </div>            <!-- Membership Info -->
            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="font-semibold text-gray-800 mb-3">Membership Information</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                  <span class="text-gray-600">Membership Fee:</span>
                  <span :class="{
                    'bg-green-100 text-green-800': selectedMemberDetails.membership_fee_id === 1,
                    'bg-blue-100 text-blue-800': selectedMemberDetails.membership_fee_id === 2,
                    'bg-purple-100 text-purple-800': selectedMemberDetails.membership_fee_id === 3
                  }" class="px-2 py-1 text-xs font-semibold rounded-full">
                    {{ getMembershipFee(selectedMemberDetails.membership_fee_id) }}
                  </span>
                </div>
                <div>
                  <span class="text-gray-600">Registration Date:</span>
                  <p class="font-medium">{{ formatDate(selectedMemberDetails.registration_date) }}</p>
                </div>
              </div>
            </div>

            <!-- Water Consumption & Meter Reading Edit Section -->
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
              <h4 class="font-semibold text-gray-800 mb-4">Water Consumption & Meter Reading</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">                <!-- Previous Reading -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Previous Meter Reading</label>
                  <input
                    v-model.number="editingReadings.prev_meter_reading"
                    type="number"
                    min="0"
                    step="0.01"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    @change="updateCumConsumption"
                    @input="updateCumConsumption"
                  />
                  <p class="text-xs text-gray-600 mt-1">Previous CUM: {{ editingReadings.prev_CUM_consumption || 0 }}</p>
                </div><!-- Present Reading -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Present Meter Reading</label>
                  <input
                    v-model.number="editingReadings.present_meter_reading"
                    type="number"
                    :min="editingReadings.prev_meter_reading || 0"
                    step="0.01"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :class="{ 'border-red-500': readingError }"
                    @change="updateCumConsumption"
                    @input="updateCumConsumption"
                  />
                  <p v-if="readingError" class="text-xs text-red-600 mt-1">{{ readingError }}</p>
                  <p v-else class="text-xs text-gray-600 mt-1">Present CUM: {{ editingReadings.present_CUM_consumption || 0 }}</p>
                </div>

                <!-- Display Current Consumption -->
                <div class="md:col-span-2 bg-white p-4 rounded border-l-4 border-blue-500">
                  <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                      <span class="text-gray-600">Calculated CUM:</span>
                      <p class="font-bold text-lg text-blue-600">{{ editingReadings.present_CUM_consumption || 0 }} CUM</p>
                    </div>
                    <div>
                      <span class="text-gray-600">Difference:</span>
                      <p class="font-bold text-lg text-green-600">{{ (editingReadings.present_meter_reading || 0) - (editingReadings.prev_meter_reading || 0) }} units</p>
                    </div>
                  </div>
                </div>
              </div>              <!-- Save Readings Button -->
              <div class="mt-4 flex gap-3">
                <button
                  @click="saveReadings"
                  :disabled="isSavingReadings || !!readingError || editingReadings.present_meter_reading < editingReadings.prev_meter_reading"
                  class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
                >
                  {{ isSavingReadings ? 'Saving...' : 'Save Meter Readings' }}
                </button>
                <button
                  @click="resetReadingsForm"
                  class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg transition-colors"
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reset Water Consumption Confirmation Modal -->
    <div
      v-if="showResetModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      @click="closeResetModal"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full" @click.stop>
        <div class="p-6">
          <div class="flex items-center mb-4">
            <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center mr-3">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Reset Water Consumption</h3>
          </div>
          
          <div class="mb-6">
            <p class="text-gray-600 mb-4">
              Are you sure you want to reset water consumption for:
            </p>
            <div class="bg-gray-50 p-3 rounded-lg">
              <p class="font-medium">{{ selectedResetMember?.account_no }} - {{ selectedResetMember?.fname }} {{ selectedResetMember?.lname }}</p>
              <p class="text-sm text-gray-600">Meter No: {{ selectedResetMember?.meter_no }}</p>
            </div>
            <div class="mt-4 p-4 bg-yellow-50 border-l-4 border-yellow-500">
              <p class="text-sm text-yellow-700">
                <strong>Warning:</strong> This action will reset all water consumption data to zero:
              </p>
              <ul class="text-sm text-yellow-700 mt-2 list-disc list-inside">
                <li>Previous CUM consumption → 0</li>
                <li>Present CUM consumption → 0</li>
                <li>Previous meter reading → 0</li>
                <li>Present meter reading → 0</li>
              </ul>
            </div>
          </div>
          
          <div class="flex gap-3">
            <button
              @click="confirmResetWaterConsumption"
              :disabled="resettingConsumption"
              class="flex-1 bg-orange-600 hover:bg-orange-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors disabled:bg-gray-400"
            >
              {{ resettingConsumption ? 'Resetting...' : 'Confirm Reset' }}
            </button>
            <button
              @click="closeResetModal"
              class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth'
})

import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useRouter, useRoute } from '#app'
import { useApi } from '~/composables/useApi'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()
const api = useApi()

const isClient = ref(false)
const members = ref([])
const loading = ref(false)
const searchQuery = ref('')
const showAddModal = ref(false)
const saving = ref(false)
const saveError = ref('')
const agreedToTerms = ref(false)
const selectedMembershipFee = ref('')
const puroks = ref([])
const tsNumbers = ref([])
const membershipFees = ref([])
const topScrollbar = ref(null)
const bottomScrollbar = ref(null)
const scrollWidth = ref(0)
let isScrolling = false
const showNotification = ref(false)
const notificationMessage = ref('')
// Add these refs after your existing refs
const photoPreview = ref(null)
const showPhotoOptions = ref(false)
const showCamera = ref(false)
const cameraStream = ref(null)
const fileInputRef = ref(null)
const videoRef = ref(null)
const canvasRef = ref(null)
const photoFile = ref(null)

// Modal state variables
const showMemberDetailsModal = ref(false)
const selectedMemberDetails = ref(null)
const showResetModal = ref(false)
const selectedResetMember = ref(null)
const resettingConsumption = ref(false)

// Water readings editing state
const editingReadings = ref({
  prev_meter_reading: 0,
  present_meter_reading: 0,
  prev_CUM_consumption: 0,
  present_CUM_consumption: 0
})
const readingError = ref('')
const isSavingReadings = ref(false)

// Shared meter detection variables
const sharedMeterInfo = ref([])
const checkingSharedMeter = ref(false)
let sharedMeterTimeout = null

// Account number validation variables
const accountNoExists = ref(false)
const checkingAccountNo = ref(false)
const accountNoError = ref('')
let accountNoTimeout = null

// Add photo handling functions
const handleFileUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    photoFile.value = file
    const reader = new FileReader()
    reader.onloadend = () => {
      photoPreview.value = reader.result
      showPhotoOptions.value = false
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
    showPhotoOptions.value = false

    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'user' },
      audio: false
    })

    // Attach after the stream is definitely ready
    videoRef.value.srcObject = stream
    cameraStream.value = stream

    await videoRef.value.play().catch(err => {
      console.warn('Video play failed initially:', err)
    })

  } catch (err) {
    if (err.name === 'AbortError') {
      console.warn('Retrying camera start...')
      setTimeout(openCamera, 1000) // retry once after 1s
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
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

// Water readings methods
const updateCumConsumption = () => {
  readingError.value = ''
  
  // Validate that present reading is not less than previous reading
  if (editingReadings.value.present_meter_reading < editingReadings.value.prev_meter_reading) {
    readingError.value = 'Present meter reading cannot be less than previous meter reading'
    editingReadings.value.present_CUM_consumption = 0
    console.log('Validation error set:', readingError.value)
    return
  }
  
  // Calculate CUM consumption
  editingReadings.value.present_CUM_consumption = 
    editingReadings.value.present_meter_reading - editingReadings.value.prev_meter_reading
  
  console.log('Validation passed. CUM:', editingReadings.value.present_CUM_consumption, 'Error:', readingError.value)
}

const saveReadings = async () => {
  console.log('saveReadings called. readingError:', readingError.value, 'isSavingReadings:', isSavingReadings.value)
  if (!selectedMemberDetails.value) {
    console.log('No member selected')
    return
  }
  if (readingError.value) {
    console.log('Reading error exists:', readingError.value)
    return
  }
  
  isSavingReadings.value = true
  try {
    // Save water consumption record only (readings are stored in water_consumptions table, not members table)
    await api('/water-consumptions', {
      method: 'POST',
      body: {
        member_Id: selectedMemberDetails.value.member_id,
        prev_CUM_consumption: editingReadings.value.prev_CUM_consumption,
        present_CUM_consumption: editingReadings.value.present_CUM_consumption,
        prev_meter_reading: editingReadings.value.prev_meter_reading,
        present_meter_reading: editingReadings.value.present_meter_reading,
        reading_date: new Date().toISOString().split('T')[0]
      }
    })
    
    // Show success notification
    notificationMessage.value = `Meter readings updated successfully for ${selectedMemberDetails.value.account_no}`
    showNotification.value = true
    setTimeout(() => {
      showNotification.value = false
    }, 5000)
      // Reload members and close modal
    await loadMembers()
    closeMemberDetailsModal()  } catch (err) {
    console.error('Failed to save readings:', err)
    alert(err.data?.message || 'Failed to save meter readings. Please try again.')
  } finally {
    isSavingReadings.value = false
  }
}

const resetReadingsForm = () => {
  if (selectedMemberDetails.value && selectedMemberDetails.value.latest_consumption) {
    const consumption = selectedMemberDetails.value.latest_consumption
    editingReadings.value = {
      prev_meter_reading: consumption.prev_meter_reading || 0,
      present_meter_reading: consumption.present_meter_reading || 0,
      prev_CUM_consumption: consumption.prev_CUM_consumption || 0,
      present_CUM_consumption: consumption.present_CUM_consumption || 0
    }
  }
  readingError.value = ''
}

const viewMemberDetails = (member) => {
  selectedMemberDetails.value = member
  // Initialize readings from latest consumption data
  if (member.latest_consumption) {
    const consumption = member.latest_consumption
    editingReadings.value = {
      prev_meter_reading: consumption.prev_meter_reading || 0,
      present_meter_reading: consumption.present_meter_reading || 0,
      prev_CUM_consumption: consumption.prev_CUM_consumption || 0,
      present_CUM_consumption: consumption.present_CUM_consumption || 0
    }
  } else {
    // No consumption data yet, start with zeros
    editingReadings.value = {
      prev_meter_reading: 0,
      present_meter_reading: 0,
      prev_CUM_consumption: 0,
      present_CUM_consumption: 0
    }
  }
  readingError.value = ''
  showMemberDetailsModal.value = true
}

const closeMemberDetailsModal = () => {
  showMemberDetailsModal.value = false
  selectedMemberDetails.value = null
  editingReadings.value = {
    prev_meter_reading: 0,
    present_meter_reading: 0,
    prev_CUM_consumption: 0,
    present_CUM_consumption: 0
  }
  readingError.value = ''
}

const resetWaterConsumption = (member) => {
  selectedResetMember.value = member
  showResetModal.value = true
}

const closeResetModal = () => {
  showResetModal.value = false
  selectedResetMember.value = null
  resettingConsumption.value = false
}

const confirmResetWaterConsumption = async () => {
  if (!selectedResetMember.value) return
  
  resettingConsumption.value = true
  try {
    await api(`/members/${selectedResetMember.value.member_id}/reset-consumption`, {
      method: 'POST'
    })
    
    // Show success notification
    notificationMessage.value = `Water consumption reset successfully for ${selectedResetMember.value.account_no}`
    showNotification.value = true
    
    // Auto-hide notification after 5 seconds
    setTimeout(() => {
      showNotification.value = false
    }, 5000)
    
    closeResetModal()
    
    // Reload members to get updated data
    await loadMembers()
  } catch (err) {
    console.error('Failed to reset water consumption:', err)
    alert('Failed to reset water consumption. Please try again.')
  } finally {
    resettingConsumption.value = false
  }
}

const newMember = ref({
  account_no: '',
  purok_id: '',
  ts_Id: '',
  meter_no: '',
  fname: '',
  mname: '',
  lname: '',
  suffix: '',
  barangay: '',
  municipality: '',
  province: '',
  zip_code: '',
  region: '',
  date_of_birth: '',
  place_of_birth: '',
  sex: '',
  civil_status: '',
  mobile_no: '',
  religion: '',
  ethnicity: '',
  language: '',
  education_attainment: '',
  school_address: '',
  course: '',
  year_graduated: '',
  height: null,
  weight: null,
  occupation: '',
  company_address: '',
  spouse_fname: '',
  spouse_mname: '',
  spouse_lname: '',
  spouse_suffix: '',
  spouse_date_of_birth: '',
  spouse_address: '',
  spouse_ethnicity: '',
  spouse_occupation: '',
  spouse_phone_no: '',
  government_type_id: '',
  government_no: ''
})

const currentPath = computed(() => route.path)

const tabs = [
  { label: 'Dashboard', path: '/president/dashboard' },
  { label: 'Members', path: '/president/members' },
  { label: 'Expenses', path: '/president/expenses' },
  { label: 'Master-list', path: '/president/master-list' },
  { label: 'Collection', path: '/president/collection' },
  { label: 'Payment Status', path: '/president/payment-status' },
  { label: 'Important Info', path: '/president/important-info' },
  { label: 'Employee', path: '/president/employee' },
  { label: 'Consumption Report', path: '/president/water-report' }
]

const totalMembers = computed(() => members.value.length)

const fullName = computed(() => {
  const parts = [
    newMember.value.fname,
    newMember.value.mname,
    newMember.value.lname,
    newMember.value.suffix
  ].filter(Boolean)
  return parts.length > 0 ? parts.join(' ') : '_______________'
})

const filteredMembers = computed(() => {
  const query = searchQuery.value.toLowerCase()
  if (!query) return members.value
  return members.value.filter(m =>
    m.account_no.toLowerCase().includes(query) ||
    m.fname.toLowerCase().includes(query) ||
    m.lname.toLowerCase().includes(query)
  )
})

const syncScroll = (source) => {
  if (isScrolling) return
  isScrolling = true
  
  if (source === 'top' && topScrollbar.value && bottomScrollbar.value) {
    bottomScrollbar.value.scrollLeft = topScrollbar.value.scrollLeft
  } else if (source === 'bottom' && topScrollbar.value && bottomScrollbar.value) {
    topScrollbar.value.scrollLeft = bottomScrollbar.value.scrollLeft
  }
  
  requestAnimationFrame(() => {
    isScrolling = false
  })
}

const updateScrollWidth = () => {
  if (bottomScrollbar.value) {
    const table = bottomScrollbar.value.querySelector('table')
    if (table) {
      scrollWidth.value = table.scrollWidth
    }
  }
}

const loadMembers = async () => {
  loading.value = true
  try {
    members.value = await api('/members')
    // Debug: Check first member's structure
    // if (members.value.length > 0) {
    //   console.log('First member data:', members.value[0])
    //   console.log('TS Number data:', members.value[0].tsNumber || members.value[0].ts_number)
    // }
    // Update scroll width after table renders
    setTimeout(() => {
      updateScrollWidth()
    }, 100)
  } catch (err) {
    console.error('Failed to load members:', err)
  } finally {
    loading.value = false
  }
}

const loadPuroks = async () => {
  try {
    console.log('Loading puroks...')
    puroks.value = await api('/puroks')
    console.log('Puroks loaded:', puroks.value)
  } catch (err) {
    console.error('Failed to load puroks:', err)
    saveError.value = 'Failed to load Puroks. Please refresh the page.'
  }
}

const loadTsNumbers = async () => {
  try {
    console.log('Loading TS numbers...')
    tsNumbers.value = await api('/ts-numbers')
    console.log('TS Numbers loaded:', tsNumbers.value)
  } catch (err) {
    console.error('Failed to load TS numbers:', err)
    saveError.value = 'Failed to load TS Numbers. Please refresh the page.'
  }
}

const loadMembershipFees = async () => {
  try {
    console.log('Loading membership fees...')
    membershipFees.value = await api('/membership-fees')
    console.log('Membership fees loaded:', membershipFees.value)
  } catch (err) {
    console.error('Failed to load membership fees:', err)
    saveError.value = 'Failed to load Membership Fees. Please refresh the page.'
  }
}

const closeAddModal = () => {
  showAddModal.value = false
  saveError.value = ''
  agreedToTerms.value = false
  selectedMembershipFee.value = ''
  removePhoto()
  
  // Clear account number validation
  accountNoExists.value = false
  checkingAccountNo.value = false
  accountNoError.value = ''
  if (accountNoTimeout) {
    clearTimeout(accountNoTimeout)
  }
  
  // Clear shared meter info
  sharedMeterInfo.value = []
  if (sharedMeterTimeout) {
    clearTimeout(sharedMeterTimeout)
  }
  
  newMember.value = {
    account_no: '',
    purok_id: '',
    ts_Id: '',
    meter_no: '',
    fname: '',
    mname: '',
    lname: '',
    suffix: '',
    barangay: '',
    municipality: '',
    province: '',
    zip_code: '',
    region: '',
    date_of_birth: '',
    place_of_birth: '',
    sex: '',
    civil_status: '',
    mobile_no: '',
    religion: '',
    ethnicity: '',
    language: '',
    education_attainment: '',
    school_address: '',
    course: '',
    year_graduated: '',
    height: null,
    weight: null,
    occupation: '',
    company_address: '',
    spouse_fname: '',
    spouse_mname: '',
    spouse_lname: '',
    spouse_suffix: '',
    spouse_date_of_birth: '',
    spouse_address: '',
    spouse_ethnicity: '',
    spouse_occupation: '',
    spouse_phone_no: '',
    government_type_id: '',
    government_no: ''  }
  // Clear shared meter info when closing
  sharedMeterInfo.value = []
}

// Function to check for shared meters
const checkSharedMeter = async () => {
  const meterNo = newMember.value.meter_no?.trim()
  
  // Clear previous timeout
  if (sharedMeterTimeout) {
    clearTimeout(sharedMeterTimeout)
  }
  
  // Clear shared meter info if empty
  if (!meterNo) {
    sharedMeterInfo.value = []
    return
  }
  
  // Debounce the API call
  sharedMeterTimeout = setTimeout(async () => {
    checkingSharedMeter.value = true
    try {
      // Search for members with the same meter number
      const existingMembers = members.value.filter(member => 
        member.meter_no === meterNo && member.meter_no !== ''
      )
      
      if (existingMembers.length > 0) {
        sharedMeterInfo.value = existingMembers
        console.log('Found shared meter members:', existingMembers)
      } else {
        sharedMeterInfo.value = []
      }
    } catch (err) {
      console.error('Error checking shared meter:', err)
      sharedMeterInfo.value = []
    } finally {
      checkingSharedMeter.value = false
    }  }, 500) // Wait 500ms after user stops typing
}

// Function to check if account number already exists
const checkAccountNumber = async () => {
  const accountNo = newMember.value.account_no?.trim()
  
  // Clear previous timeout
  if (accountNoTimeout) {
    clearTimeout(accountNoTimeout)
  }
  
  // Clear validation state if empty
  if (!accountNo) {
    accountNoExists.value = false
    accountNoError.value = ''
    return
  }
  
  // Debounce the validation
  accountNoTimeout = setTimeout(async () => {
    checkingAccountNo.value = true
    accountNoError.value = ''
    
    try {
      // Check if account number exists in current members list
      const existingMember = members.value.find(member => 
        member.account_no === accountNo
      )
      
      if (existingMember) {
        accountNoExists.value = true
        accountNoError.value = `Account number already exists (${existingMember.fname} ${existingMember.lname})`
      } else {
        accountNoExists.value = false
        accountNoError.value = ''
      }
    } catch (err) {
      console.error('Error checking account number:', err)
      accountNoError.value = 'Error validating account number'
    } finally {
      checkingAccountNo.value = false
    }
  }, 500) // Wait 500ms after user stops typing
}

const saveMember = async () => {
  // Check for account number validation before submitting
  if (accountNoExists.value) {
    saveError.value = 'Account number already exists. Please use a different account number.'
    return
  }
  
  if (!agreedToTerms.value) {
    saveError.value = 'You must agree to the terms and conditions'
    return
  }

  if (!selectedMembershipFee.value) {
    saveError.value = 'Please select a membership category'
    return
  }

  saving.value = true
  saveError.value = ''

  try {
    // Create FormData to handle file upload
    const formData = new FormData()
    
    // Add all member data
    Object.keys(newMember.value).forEach(key => {
      if (newMember.value[key] !== null && newMember.value[key] !== '') {
        formData.append(key, newMember.value[key])
      }
    })
    
    // Add membership fee
    formData.append('membership_fee_id', parseInt(selectedMembershipFee.value))
    
    // Add photo if exists
    if (photoFile.value) {
      formData.append('photo', photoFile.value)
    }    // Send as multipart/form-data
    const response = await api('/members', {
      method: 'POST',
      body: formData
    })

    await loadMembers()
    closeAddModal()
    
    // Show success notification with shared meter info if applicable
    if (response.shared_meter_info) {
      notificationMessage.value = `Member added successfully! Copied ${response.shared_meter_info.consumption_records_copied} consumption records from shared meter.`
    } else {
      notificationMessage.value = 'Member added successfully!'
    }
    showNotification.value = true
    
    // Auto-hide notification after 5 seconds
    setTimeout(() => {
      showNotification.value = false
    }, 5000)
  } catch (err) {
    saveError.value = err.message || 'Failed to save member. Please try again.'
    console.error('Failed to save member:', err)
  } finally {
    saving.value = false
  }
}

const viewMember = (id) => {
  router.push(`/president/members-edit/${id}`)
}

const viewPaymentHistory = (id) => {
  router.push(`/president/member-payment-history/${id}`)
}

const deleteMember = async (id) => {
  if (!confirm('Are you sure you want to delete this member?')) return
  try {
    removePhoto()
    await api(`/members/${id}`, { method: 'DELETE' })
    await loadMembers()
  } catch (err) {
    alert('Failed to delete member')
  }
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  })
}

const getSpouseName = (member) => {
  const parts = [
    member.spouse_fname,
    member.spouse_mname ? member.spouse_mname.charAt(0) + '.' : '',
    member.spouse_lname,
    member.spouse_suffix
  ].filter(Boolean)
  return parts.length > 0 ? parts.join(' ') : '-'
}

const getMembershipFee = (feeId) => {
  const fee = membershipFees.value.find(f => f.membership_fee_id === feeId)
  return fee ? `₱${fee.fee_amount.toFixed(2)}` : '-'
}

const handleLogout = async () => {
  await authStore.logout()
  navigateTo('/')
}

const mobileError = ref('')

const validateMobileNumber = () => {
  if (!newMember.value.mobile_no) {
    mobileError.value = ''
    return
  }

  const mobileRegex = /^09\d{9}$/
  if (!mobileRegex.test(newMember.value.mobile_no)) {
    mobileError.value = 'Please enter a valid 11-digit mobile number starting with 09'
    newMember.value.mobile_no = newMember.value.mobile_no.slice(0, 11)
  } else {
    mobileError.value = ''
  }
}

onMounted(() => {
  isClient.value = true
  authStore.initAuth()
  loadMembers()
  loadPuroks()
  loadTsNumbers()
  loadMembershipFees()
    // Check for success notification from sessionStorage
  const successMsg = sessionStorage.getItem('memberUpdateSuccess')
  if (successMsg) {
    notificationMessage.value = successMsg
    showNotification.value = true
    sessionStorage.removeItem('memberUpdateSuccess')
    
    // Auto-hide notification after 5 seconds
    setTimeout(() => {
      showNotification.value = false
    }, 5000)
  }
  
  // Update scroll width after data loads
  setTimeout(() => {
    updateScrollWidth()
  }, 500)
})</script>

<style scoped>
@keyframes slide-in {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.animate-slide-in {
  animation: slide-in 0.3s ease-out;
}
</style>