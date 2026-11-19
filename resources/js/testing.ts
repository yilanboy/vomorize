import { learningProgressDb } from '@/lib/learningProgressDb';

// Expose the guest progress store in the testing environment so browser tests can
// seed and inspect IndexedDB through the same API the app uses.
window.__learningProgressDb = learningProgressDb;
