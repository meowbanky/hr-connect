<!-- Schedule Interview Modal -->
<div id="scheduleModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeScheduleModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <div class="bg-white dark:bg-slate-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-slate-900 dark:text-white" id="modal-title">Schedule Interview</h3>
                        <div class="mt-2">
                            <form id="scheduleForm" class="space-y-4">
                                <input type="hidden" name="application_id" id="schedule_application_id" value="">
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Date</label>
                                        <input type="date" name="interview_date" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-primary focus:ring-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Time</label>
                                        <input type="time" name="interview_time" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-primary focus:ring-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white sm:text-sm">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Venue Name</label>
                                    <input type="text" name="venue_name" placeholder="e.g. Head Office, Conference Room A" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-primary focus:ring-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white sm:text-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Venue Address</label>
                                    <textarea name="venue_address" rows="2" placeholder="Full address" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-primary focus:ring-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white sm:text-sm"></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Google Maps Link (Optional)</label>
                                    <input type="url" name="venue_link" id="venue_link" placeholder="https://maps.google.com/..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-primary focus:ring-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white sm:text-sm">
                                    <p class="text-xs text-slate-500 mt-1">Paste a link to help candidates find the location.</p>
                                </div>

                                <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Map Location Picker</label>
                                    <input type="text" id="map-search" placeholder="Search for a location..." class="block w-full rounded-md border-slate-300 shadow-sm focus:border-primary focus:ring-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white sm:text-sm mb-2">
                                    <div id="map" style="height: 300px; width: 100%; border-radius: 8px;" class="border border-slate-300 dark:border-slate-600"></div>
                                    <p class="text-xs text-slate-500 mt-1">Search or click on the map to set the location.</p>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Latitude (Optional)</label>
                                        <input type="text" name="venue_lat" id="venue_lat" placeholder="e.g. 6.5244" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-primary focus:ring-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white sm:text-sm" readonly>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Longitude (Optional)</label>
                                        <input type="text" name="venue_lng" id="venue_lng" placeholder="e.g. 3.3792" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-primary focus:ring-primary dark:bg-slate-700 dark:border-slate-600 dark:text-white sm:text-sm" readonly>
                                    </div>
                                </div>
                                <p class="text-xs text-yellow-600 dark:text-yellow-500">
                                    <span class="material-symbols-outlined text-[14px] align-text-bottom">warning</span>
                                    Lat/Lng required for location check-in validation.
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-slate-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="submitSchedule()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm">
                    Schedule Interview
                </button>
                <button type="button" onclick="closeScheduleModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-slate-800 dark:text-slate-300 dark:border-slate-600 dark:hover:bg-slate-700">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>
