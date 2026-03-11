<script setup>
import { ref, computed } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/Student/AuthenticatedLayout.vue";

const props = defineProps({
  notifications: Object,
});

const page = usePage();

const unreadCount = computed(
  () => page.props.auth?.unreadNotifications || 0
);

const selectedNotification = ref(null);

const openNotification = (notification) => {
  selectedNotification.value = notification;
  if (!notification.read_at) {
    markAsRead(notification.id);
  }
};

const closeModal = () => {
  selectedNotification.value = null;
};

const timeAgo = (date) => {
  const seconds = Math.floor((new Date() - new Date(date)) / 1000);
  const intervals = [
    { label: "y", seconds: 31536000 },
    { label: "mo", seconds: 2592000 },
    { label: "d", seconds: 86400 },
    { label: "h", seconds: 3600 },
    { label: "m", seconds: 60 },
  ];
  for (const interval of intervals) {
    const count = Math.floor(seconds / interval.seconds);
    if (count >= 1) return `${count}${interval.label} ago`;
  }
  return "Just now";
};

const markAsRead = (id) => {
  // Optimistically update the local state
  const notification = props.notifications.data.find((n) => n.id === id);
  if (notification && !notification.read_at) {
    notification.read_at = new Date().toISOString();
    page.props.auth.unreadNotifications = Math.max(0, unreadCount.value - 1);
  }

  router.patch(route("student.notifications.mark-read", id), {
    preserveScroll: true,
    preserveState: true,
  });
};

const markAllAsRead = () => {
  // Optimistically update all notifications
  props.notifications.data.forEach((n) => {
    if (!n.read_at) {
      n.read_at = new Date().toISOString();
    }
  });
  page.props.auth.unreadNotifications = 0;

  router.post(route("student.notifications.mark-all-read"), {
    preserveScroll: true,
    preserveState: true,
  });
};

const priorityClass = (priority) => {
  switch (priority) {
    case "high":
      return "bg-red-100 text-red-700";
    case "low":
      return "bg-gray-100 text-gray-600";
    default:
      return "bg-blue-100 text-blue-700";
  }
};
</script>

<template>
  <Head title="Notifications" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Notifications
      </h2>
    </template>

    <div class="max-w-3xl mx-auto">
      <!-- Header bar -->
      <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">
          {{ unreadCount }} unread notification{{ unreadCount !== 1 ? "s" : "" }}
        </p>
        <button
          v-if="unreadCount > 0"
          @click="markAllAsRead"
          class="text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors"
        >
          Mark all as read
        </button>
      </div>

      <!-- Notification list -->
      <div
        v-if="notifications.data.length > 0"
        class="bg-white rounded-xl shadow divide-y divide-gray-100"
      >
        <div
          v-for="notification in notifications.data"
          :key="notification.id"
          class="flex items-start gap-4 px-5 py-4 transition-colors cursor-pointer hover:bg-gray-50"
          :class="notification.read_at ? 'bg-white' : 'bg-blue-50/50'"
          @click="openNotification(notification)"
        >
          <!-- Unread dot -->
          <div class="pt-1.5 shrink-0">
            <span
              v-if="!notification.read_at"
              class="block h-2.5 w-2.5 rounded-full bg-blue-500"
            ></span>
            <span v-else class="block h-2.5 w-2.5"></span>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <h3
                class="text-sm font-semibold text-gray-900 truncate"
                :class="{ 'font-bold': !notification.read_at }"
              >
                {{ notification.title }}
              </h3>
              <span
                v-if="notification.priority !== 'normal'"
                class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium uppercase"
                :class="priorityClass(notification.priority)"
              >
                {{ notification.priority }}
              </span>
            </div>

            <p class="text-sm text-gray-600 line-clamp-2">
              {{ notification.message }}
            </p>

            <div class="flex items-center gap-3 mt-2">
              <span class="text-xs text-gray-400">
                {{ timeAgo(notification.created_at) }}
              </span>
              <span class="text-xs text-gray-300">&middot;</span>
              <span class="text-xs text-gray-400 capitalize">
                {{ notification.type }}
              </span>
            </div>
          </div>

          <!-- Mark as read button -->
          <button
            v-if="!notification.read_at"
            @click.stop="markAsRead(notification.id)"
            class="shrink-0 p-1.5 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
            title="Mark as read"
          >
            <span class="material-symbols-outlined text-[18px]">done</span>
          </button>
        </div>
      </div>

      <!-- Empty state -->
      <div
        v-else
        class="bg-white rounded-xl shadow flex flex-col items-center justify-center py-16"
      >
        <span class="material-symbols-outlined text-gray-300 text-6xl mb-4"
          >notifications_none</span
        >
        <p class="text-gray-500 text-sm">No notifications yet.</p>
      </div>

      <!-- Pagination -->
      <div
        v-if="notifications.last_page > 1"
        class="flex items-center justify-center gap-2 mt-6"
      >
        <Link
          v-for="link in notifications.links"
          :key="link.label"
          :href="link.url"
          v-html="link.label"
          class="px-3 py-1.5 text-sm rounded-md transition-colors"
          :class="
            link.active
              ? 'bg-gray-800 text-white'
              : link.url
                ? 'text-gray-600 hover:bg-gray-100'
                : 'text-gray-300 pointer-events-none'
          "
        />
      </div>
    </div>

    <!-- Notification Modal -->
    <Teleport to="body">
      <div
        v-if="selectedNotification"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="closeModal"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/40"></div>

        <!-- Modal -->
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[80vh] flex flex-col">
          <!-- Header -->
          <div class="px-6 pt-6 pb-4 border-b border-gray-100">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <h3 class="text-lg font-semibold text-gray-900">
                  {{ selectedNotification.title }}
                </h3>
                <div class="flex items-center gap-2 mt-1">
                  <span class="text-xs text-gray-400">
                    {{ timeAgo(selectedNotification.created_at) }}
                  </span>
                  <span class="text-xs text-gray-300">&middot;</span>
                  <span class="text-xs text-gray-400 capitalize">
                    {{ selectedNotification.type }}
                  </span>
                  <span
                    v-if="selectedNotification.priority !== 'normal'"
                    class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium uppercase"
                    :class="priorityClass(selectedNotification.priority)"
                  >
                    {{ selectedNotification.priority }}
                  </span>
                </div>
              </div>
              <button
                @click="closeModal"
                class="shrink-0 p-1 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
              >
                <span class="material-symbols-outlined text-[20px]">close</span>
              </button>
            </div>
          </div>

          <!-- Body -->
          <div
            class="px-6 py-4 overflow-y-auto text-sm text-gray-700 leading-relaxed prose prose-sm max-w-none"
            v-html="selectedNotification.message"
          ></div>

          <!-- Footer -->
          <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
            <button
              @click="closeModal"
              class="px-5 py-2 bg-gray-800 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors"
            >
              OK
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AuthenticatedLayout>
</template>
