from PySide6.QtWidgets import QApplication, QWidget, QPushButton, QVBoxLayout, QLabel, QWhatsThis
import sys

class WhatsThisDemo(QWidget):
    def __init__(self):
        super().__init__()
        
        self.setWindowTitle("Pyhton")
        
        self.label = QLabel("Click 'What's This' and then on a button to see help text.")
        self.info_label = QLabel("This text can be toggled on/off.")
        
        self.button1 = QPushButton("Button 1")
        self.button2 = QPushButton("Button 2")
        self.whats_this_button = QPushButton("What's This Mode")
        self.toggle_button = QPushButton("Toggle Text")
        
        # Assigning QWhatsThis help text using setWhatsThis
        self.button1.setWhatsThis("This is Button 1, it does something cool!")
        self.button2.setWhatsThis("This is Button 2, it does another cool thing!")
        
        # Connect buttons
        self.whats_this_button.clicked.connect(self.toggleWhatsThisMode)
        self.toggle_button.clicked.connect(self.toggleText)
        
        layout = QVBoxLayout()
        layout.addWidget(self.label)
        layout.addWidget(self.button1)
        layout.addWidget(self.button2)
        layout.addWidget(self.whats_this_button)
        layout.addWidget(self.toggle_button)
        layout.addWidget(self.info_label)
        
        self.setLayout(layout)
        
    def toggleWhatsThisMode(self):
        if QWhatsThis.inWhatsThisMode():  # Corrected method call
            QWhatsThis.leaveWhatsThisMode()
        else:
            QWhatsThis.enterWhatsThisMode()
    
    def toggleText(self):
        if self.info_label.isVisible():
            self.hideText()
        else:
            self.showText()
    
    def hideText(self):
        self.info_label.hide()
    
    def showText(self):
        self.info_label.show()
        
if __name__ == "__main__":
    app = QApplication(sys.argv)
    window = WhatsThisDemo()
    window.show()
    sys.exit(app.exec())
