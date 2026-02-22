/**
 * Genetic Algorithm Schedule Generation UI
 * Advanced interaction handling and utilities
 */

class ScheduleGenerationUI {
    constructor() {
        this.state = {
            isRunning: false,
            currentGeneration: 0,
            totalGenerations: 0,
            bestFitness: 0,
            conflictCount: 0,
            generationHistory: [],
            selectedScheduleItem: null
        };

        this.config = {
            progressUpdateInterval: 400,
            autoScrollDelay: 300,
            toastDuration: 4000,
            animationDuration: 300
        };

        this.initializeEventListeners();
        this.initializeTooltips();
    }

    /**
     * Initialize Event Listeners
     */
    initializeEventListeners() {
        // Form validation
        const form = document.getElementById('scheduleConfigForm');
        if (form) {
            form.addEventListener('submit', (e) => e.preventDefault());
            form.addEventListener('change', this.validateForm.bind(this));
        }

        // View toggle
        const gridViewRadio = document.getElementById('gridView');
        const tableViewRadio = document.getElementById('tableView');
        
        if (gridViewRadio) {
            gridViewRadio.addEventListener('change', () => this.switchView('grid'));
        }
        if (tableViewRadio) {
            tableViewRadio.addEventListener('change', () => this.switchView('table'));
        }

        // Schedule item click handlers
        this.attachScheduleItemClickHandlers();

        // Window resize handler for responsive adjustments
        window.addEventListener('resize', this.handleWindowResize.bind(this));
    }

    /**
     * Initialize Bootstrap Tooltips
     */
    initializeTooltips() {
        const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipElements.forEach(element => {
            if (typeof bootstrap !== 'undefined') {
                new bootstrap.Tooltip(element, {
                    trigger: 'hover'
                });
            }
        });
    }

    /**
     * Validate Form
     */
    validateForm() {
        const form = document.getElementById('scheduleConfigForm');
        const isValid = form.checkValidity();
        
        const generateBtn = document.getElementById('generateScheduleBtn');
        if (generateBtn) {
            generateBtn.disabled = !isValid || this.state.isRunning;
        }

        return isValid;
    }

    /**
     * Show Confirmation Modal
     */
    showConfirmationModal() {
        if (!this.validateForm()) {
            document.getElementById('scheduleConfigForm').reportValidity();
            return;
        }

        this.populateConfirmationSummary();
        
        if (typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            modal.show();
        }
    }

    /**
     * Populate Confirmation Summary
     */
    populateConfirmationSummary() {
        const programSelect = document.getElementById('program');
        const yearLevelSelect = document.getElementById('yearLevel');
        const blockSectionSelect = document.getElementById('blockSection');
        const semesterSelect = document.getElementById('semester');

        const getValue = (select) => select ? select.options[select.selectedIndex]?.text : '--';

        document.getElementById('summaryProgram').textContent = `Program: ${getValue(programSelect)}`;
        document.getElementById('summaryYearLevel').textContent = `Year Level: ${getValue(yearLevelSelect)}`;
        document.getElementById('summaryBlockSection').textContent = `Block/Section: ${getValue(blockSectionSelect)}`;
        document.getElementById('summarySemester').textContent = `Semester: ${getValue(semesterSelect)}`;
    }

    /**
     * Execute Generation
     */
    executeGeneration() {
        this.state.isRunning = true;
        this.state.currentGeneration = 0;
        this.state.totalGenerations = parseInt(document.getElementById('generations').value) || 100;

        // Close confirmation modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmationModal'));
        if (modal) modal.hide();

        // Update UI state
        this.updateUIForGeneration(true);
        this.startGeneration();
    }

    /**
     * Update UI For Generation
     */
    updateUIForGeneration(isRunning) {
        const formInputs = document.getElementById('scheduleConfigForm').querySelectorAll('input, select, button');
        formInputs.forEach(input => {
            if (input.id !== 'stopButton' && input.id !== 'generateScheduleBtn') {
                input.disabled = isRunning;
            }
        });

        const generateBtn = document.getElementById('generateScheduleBtn');
        const stopBtn = document.getElementById('stopButton');
        if (generateBtn) generateBtn.disabled = isRunning;
        if (stopBtn) stopBtn.disabled = !isRunning;

        const runningIndicator = document.getElementById('runningIndicator');
        const successIndicator = document.getElementById('successIndicator');

        if (isRunning) {
            if (runningIndicator) runningIndicator.classList.remove('d-none');
            if (successIndicator) successIndicator.classList.add('d-none');
            this.updateStatusBadge('Running');
        } else {
            if (runningIndicator) runningIndicator.classList.add('d-none');
            if (successIndicator) successIndicator.classList.add('d-none');
            this.updateStatusBadge('Idle');
        }
    }

    /**
     * Start Generation (Simulation)
     */
    startGeneration() {
        let progress = 0;
        const progressInterval = this.config.progressUpdateInterval;

        const generationTimer = setInterval(() => {
            if (!this.state.isRunning) {
                clearInterval(generationTimer);
                return;
            }

            // Simulate non-linear progress
            const randomIncrement = Math.random() * 12 + 3; // 3-15% increment
            progress += randomIncrement;

            if (progress > 100) progress = 100;

            // Update state
            this.state.currentGeneration = Math.floor((progress / 100) * this.state.totalGenerations);
            this.state.bestFitness = 95 - (100 - progress) * 0.5; // Simulated fitness

            // Update UI
            this.updateProgress(progress);
            this.updateCurrentGeneration(this.state.currentGeneration, this.state.totalGenerations);
            this.updateFitnessScore(this.state.bestFitness);
            this.updateConflictCount(Math.max(0, Math.floor((100 - progress) / 20)));

            if (progress >= 100) {
                clearInterval(generationTimer);
                this.completeGeneration();
            }
        }, progressInterval);

        // Stop button handler
        const stopBtn = document.getElementById('stopButton');
        if (stopBtn) {
            stopBtn.onclick = () => {
                this.state.isRunning = false;
                clearInterval(generationTimer);
                this.cancelGeneration();
            };
        }
    }

    /**
     * Complete Generation
     */
    completeGeneration() {
        this.state.isRunning = false;
        this.updateStatusBadge('Completed');
        
        const runningIndicator = document.getElementById('runningIndicator');
        if (runningIndicator) runningIndicator.classList.add('d-none');

        const successIndicator = document.getElementById('successIndicator');
        if (successIndicator) successIndicator.classList.remove('d-none');

        this.updateUIForGeneration(false);
        this.populateSampleSchedule();
        this.showToast('Schedule generated successfully!');
        this.showConflictSummary(false); // No conflicts in successful generation
        
        // Store in history
        this.addToHistory();

        // Auto-scroll to preview
        setTimeout(() => {
            this.scrollToPreview();
        }, this.config.autoScrollDelay);
    }

    /**
     * Cancel Generation
     */
    cancelGeneration() {
        this.updateStatusBadge('Idle');
        this.updateUIForGeneration(false);
        this.updateProgress(0);
        this.updateCurrentGeneration(0, 0);
        this.showToast('Schedule generation cancelled');
    }

    /**
     * Update Progress
     */
    updateProgress(percentage) {
        const progressBar = document.getElementById('progressBar');
        if (progressBar) {
            progressBar.style.width = percentage + '%';
            progressBar.setAttribute('aria-valuenow', percentage);
        }

        const progressPercentage = document.getElementById('progressPercentage');
        if (progressPercentage) {
            progressPercentage.textContent = Math.floor(percentage) + '%';
        }
    }

    /**
     * Update Current Generation
     */
    updateCurrentGeneration(current, total) {
        const generationDisplay = document.getElementById('currentGeneration');
        if (generationDisplay) {
            generationDisplay.textContent = `${current} / ${total}`;
        }
    }

    /**
     * Update Fitness Score
     */
    updateFitnessScore(score) {
        const fitnessDisplay = document.getElementById('bestFitnessScore');
        if (fitnessDisplay) {
            fitnessDisplay.textContent = score.toFixed(2);
        }
    }

    /**
     * Update Conflict Count
     */
    updateConflictCount(count) {
        this.state.conflictCount = count;
        const conflictDisplay = document.getElementById('conflictCount');
        if (conflictDisplay) {
            conflictDisplay.textContent = count;
            conflictDisplay.style.color = count > 0 ? '#dc3545' : '#28a745';
        }
    }

    /**
     * Update Status Badge
     */
    updateStatusBadge(status) {
        const badge = document.getElementById('statusBadge');
        if (!badge) return;

        badge.classList.remove('bg-secondary', 'bg-primary', 'bg-success', 'bg-danger');

        const statusConfig = {
            'Idle': { class: 'bg-secondary', icon: 'fa-pause-circle' },
            'Running': { class: 'bg-primary', icon: 'fa-spinner fa-spin' },
            'Completed': { class: 'bg-success', icon: 'fa-check-circle' },
            'Failed': { class: 'bg-danger', icon: 'fa-exclamation-circle' }
        };

        const config = statusConfig[status] || statusConfig['Idle'];
        badge.classList.add(config.class);
        badge.innerHTML = `<i class="fas ${config.icon} me-1"></i> ${status}`;
    }

    /**
     * Populate Sample Schedule
     */
    populateSampleSchedule() {
        const sampleSchedule = [
            {subject: 'CS-101', instructor: 'Dr. Smith', room: 'Lab 301', day: 'Monday', time: '09:00', type: 'lecture'},
            {subject: 'CS-102', instructor: 'Prof. Jones', room: 'Lab 302', day: 'Tuesday', time: '10:00', type: 'lab'},
            {subject: 'CS-103', instructor: 'Dr. Brown', room: 'Room 405', day: 'Wednesday', time: '13:00', type: 'lecture'},
            {subject: 'CS-104', instructor: 'Prof. Lee', room: 'Lab 301', day: 'Thursday', time: '14:00', type: 'lab'},
            {subject: 'CS-105', instructor: 'Dr. Davis', room: 'Room 406', day: 'Friday', time: '11:00', type: 'lecture'},
            {subject: 'CS-106', instructor: 'Prof. Wilson', room: 'Lab 303', day: 'Monday', time: '13:00', type: 'lab'},
            {subject: 'CS-107', instructor: 'Dr. Taylor', room: 'Room 407', day: 'Wednesday', time: '09:00', type: 'lecture'},
            {subject: 'CS-108', instructor: 'Prof. Anderson', room: 'Lab 302', day: 'Friday', time: '14:00', type: 'lab'},
        ];

        // Clear existing schedule
        document.querySelectorAll('.schedule-slot').forEach(slot => {
            slot.innerHTML = '';
        });

        // Populate grid view
        sampleSchedule.forEach(item => {
            const dayIndex = this.getDayIndex(item.day);
            const slot = document.querySelector(`[data-day="${dayIndex}"][data-time="${item.time}"]`);
            
            if (slot) {
                const scheduleItem = document.createElement('div');
                scheduleItem.className = `schedule-item ${item.type}`;
                scheduleItem.innerHTML = `
                    <strong>${item.subject}</strong>
                    <small>${item.instructor}</small>
                    <small>${item.room}</small>
                `;
                scheduleItem.title = `${item.subject}\n${item.instructor}\n${item.room}\n${item.day} ${item.time}`;
                scheduleItem.style.cursor = 'pointer';
                scheduleItem.addEventListener('click', (e) => this.showScheduleItemDetails(item, e));
                slot.appendChild(scheduleItem);
            }
        });

        // Populate table view
        const tableBody = document.getElementById('tableViewBody');
        if (tableBody) {
            tableBody.innerHTML = sampleSchedule.map((item, index) => `
                <tr onclick="scheduleUI.selectTableRow(this, ${index})">
                    <td><strong>${item.subject}</strong></td>
                    <td>${item.instructor}</td>
                    <td>${item.room}</td>
                    <td>${item.day}</td>
                    <td>${item.time}</td>
                    <td><span class="badge bg-info">${item.type.charAt(0).toUpperCase() + item.type.slice(1)}</span></td>
                    <td><span class="badge bg-success">Scheduled</span></td>
                </tr>
            `).join('');
        }
    }

    /**
     * Get Day Index
     */
    getDayIndex(dayName) {
        const days = {
            'Monday': 0,
            'Tuesday': 1,
            'Wednesday': 2,
            'Thursday': 3,
            'Friday': 4,
            'Saturday': 5
        };
        return days[dayName] || 0;
    }

    /**
     * Show Schedule Item Details
     */
    showScheduleItemDetails(item, event) {
        event.stopPropagation();
        console.log('Schedule item details:', item);
        
        // Create a simple tooltip or detail view
        const title = `${item.subject} - ${item.instructor}\n${item.room}\n${item.day} ${item.time}`;
        event.target.closest('.schedule-item').title = title;
    }

    /**
     * Select Table Row
     */
    selectTableRow(row, index) {
        document.querySelectorAll('#tableViewBody tr').forEach(tr => {
            tr.style.backgroundColor = '';
        });
        row.style.backgroundColor = '#f8f9fa';
        this.state.selectedScheduleItem = index;
    }

    /**
     * Switch View
     */
    switchView(viewType) {
        const gridContainer = document.getElementById('gridViewContainer');
        const tableContainer = document.getElementById('tableViewContainer');

        if (viewType === 'grid') {
            gridContainer?.classList.remove('d-none');
            tableContainer?.classList.add('d-none');
        } else {
            gridContainer?.classList.add('d-none');
            tableContainer?.classList.remove('d-none');
        }
    }

    /**
     * Attach Schedule Item Click Handlers
     */
    attachScheduleItemClickHandlers() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.schedule-item')) {
                const item = e.target.closest('.schedule-item');
                item.style.opacity = '0.8';
                setTimeout(() => {
                    item.style.opacity = '1';
                }, 200);
            }
        });
    }

    /**
     * Export to PDF
     */
    exportToPDF() {
        try {
            const form = document.getElementById('scheduleConfigForm');
            const program = form.querySelector('#program')?.options[form.querySelector('#program')?.selectedIndex]?.text;
            const filename = `schedule-${program}-${new Date().toISOString().split('T')[0]}.pdf`;
            
            // Using browser's print dialog as PDF export
            // TODO: Implement actual PDF export using jsPDF library
            window.print();
            this.showToast('Opening print dialog...');
        } catch (error) {
            console.error('PDF export error:', error);
            this.showToast('Error exporting PDF', 'error');
        }
    }

    /**
     * Export to CSV
     */
    exportToCSV() {
        try {
            const tableBody = document.getElementById('tableViewBody');
            if (!tableBody || tableBody.innerHTML === '') {
                this.showToast('No schedule to export', 'warning');
                return;
            }

            let csv = 'Subject,Instructor,Room,Day,Time,Type,Status\n';
            tableBody.querySelectorAll('tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                const rowData = Array.from(cells).map(cell => {
                    const text = cell.textContent.trim().replace(/\n/g, ' ');
                    return `"${text}"`;
                }).join(',');
                csv += rowData + '\n';
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `schedule-${new Date().toISOString().split('T')[0]}.csv`;
            link.click();

            this.showToast('Schedule exported successfully!');
        } catch (error) {
            console.error('CSV export error:', error);
            this.showToast('Error exporting CSV', 'error');
        }
    }

    /**
     * Show Conflict Summary
     */
    showConflictSummary(hasConflicts) {
        const noConflictsState = document.getElementById('noConflictsState');
        const conflictsState = document.getElementById('conflictsState');

        if (hasConflicts) {
            noConflictsState?.classList.add('d-none');
            conflictsState?.classList.remove('d-none');
        } else {
            noConflictsState?.classList.remove('d-none');
            conflictsState?.classList.add('d-none');
        }
    }

    /**
     * Approve and Submit
     */
    approveAndSubmit() {
        if (confirm('Are you sure you want to approve and submit this schedule? This action cannot be undone.')) {
            // TODO: Send approval to backend
            this.showToast('Schedule approved and submitted successfully!');
        }
    }

    /**
     * Reset to Default Parameters
     */
    resetToDefaults() {
        document.getElementById('populationSize').value = 50;
        document.getElementById('generations').value = 100;
        document.getElementById('mutationRate').value = 15;
        document.getElementById('crossoverRate').value = 80;
        document.getElementById('eliteSize').value = 5;
        this.showToast('Parameters reset to defaults');
    }

    /**
     * Show Toast Notification
     */
    showToast(message, type = 'success') {
        const toastContainer = document.getElementById('successToast');
        if (!toastContainer) return;

        const colorMap = {
            'success': '#28a745',
            'error': '#dc3545',
            'warning': '#ffc107',
            'info': '#17a2b8'
        };

        toastContainer.style.display = 'block';
        toastContainer.style.backgroundColor = colorMap[type] || colorMap['success'];
        
        const messageElement = document.getElementById('toastMessage');
        if (messageElement) {
            messageElement.textContent = message;
        }

        setTimeout(() => {
            toastContainer.style.display = 'none';
        }, this.config.toastDuration);
    }

    /**
     * Scroll to Preview
     */
    scrollToPreview() {
        const previewSection = document.getElementById('previewSection');
        if (previewSection) {
            previewSection.scrollIntoView({behavior: 'smooth', block: 'start'});
        }
    }

    /**
     * Add to History
     */
    addToHistory() {
        const form = document.getElementById('scheduleConfigForm');
        const program = form.querySelector('#program')?.options[form.querySelector('#program')?.selectedIndex]?.text;

        const historyEntry = {
            date: new Date().toLocaleString(),
            program: program,
            bestFitness: this.state.bestFitness.toFixed(2),
            conflicts: this.state.conflictCount,
            status: this.state.conflictCount === 0 ? 'Success' : 'Warning'
        };

        this.state.generationHistory.unshift(historyEntry);
        this.updateHistoryDisplay();
    }

    /**
     * Update History Display
     */
    updateHistoryDisplay() {
        const historyTableBody = document.getElementById('historyTableBody');
        if (!historyTableBody) return;

        if (this.state.generationHistory.length === 0) {
            historyTableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="fas fa-inbox me-2"></i> No generation history yet
                    </td>
                </tr>
            `;
            return;
        }

        historyTableBody.innerHTML = this.state.generationHistory.map(entry => `
            <tr>
                <td>${entry.date}</td>
                <td>${entry.program}</td>
                <td>${entry.bestFitness}</td>
                <td>${entry.conflicts}</td>
                <td><span class="badge bg-${entry.status === 'Success' ? 'success' : 'warning'}">${entry.status}</span></td>
            </tr>
        `).join('');
    }

    /**
     * Handle Window Resize
     */
    handleWindowResize() {
        // Adjust responsive layouts if needed
        console.log('Window resized');
    }
}

// Global instance
let scheduleUI;

document.addEventListener('DOMContentLoaded', () => {
    scheduleUI = new ScheduleGenerationUI();
    
    // Bind global functions for onclick handlers
    window.showConfirmationModal = () => scheduleUI.showConfirmationModal();
    window.executeGeneration = () => scheduleUI.executeGeneration();
    window.resetToDefaults = () => scheduleUI.resetToDefaults();
    window.switchView = (viewType) => scheduleUI.switchView(viewType);
    window.exportToPDF = () => scheduleUI.exportToPDF();
    window.exportToCSV = () => scheduleUI.exportToCSV();
    window.approveAndSubmit = () => scheduleUI.approveAndSubmit();
    window.scrollToPreview = () => scheduleUI.scrollToPreview();
    window.showConflictSummary = (hasConflicts) => scheduleUI.showConflictSummary(hasConflicts);
});
