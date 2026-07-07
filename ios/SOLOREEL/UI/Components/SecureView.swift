import SwiftUI
import UIKit

struct SecureView<Content: View>: UIViewRepresentable {
    let content: Content

    func makeUIView(context: Context) -> UIView {
        let container = UIView()
        let textField = UITextField()
        textField.isSecureTextEntry = true
        
        // Find the secure canvas/container subview inside UITextField
        if let secureContainer = textField.subviews.first {
            secureContainer.translatesAutoresizingMaskIntoConstraints = false
            container.addSubview(secureContainer)
            
            let hosting = UIHostingController(rootView: content)
            hosting.view.translatesAutoresizingMaskIntoConstraints = false
            hosting.view.backgroundColor = .clear
            
            secureContainer.addSubview(hosting.view)
            secureContainer.isUserInteractionEnabled = true
            
            NSLayoutConstraint.activate([
                secureContainer.topAnchor.constraint(equalTo: container.topAnchor),
                secureContainer.bottomAnchor.constraint(equalTo: container.bottomAnchor),
                secureContainer.leadingAnchor.constraint(equalTo: container.leadingAnchor),
                secureContainer.trailingAnchor.constraint(equalTo: container.trailingAnchor),
                
                hosting.view.topAnchor.constraint(equalTo: secureContainer.topAnchor),
                hosting.view.bottomAnchor.constraint(equalTo: secureContainer.bottomAnchor),
                hosting.view.leadingAnchor.constraint(equalTo: secureContainer.leadingAnchor),
                hosting.view.trailingAnchor.constraint(equalTo: secureContainer.trailingAnchor)
            ])
        }
        return container
    }

    func updateUIView(_ uiView: UIView, context: Context) {}
}

extension View {
    func preventScreenshot() -> some View {
        SecureView(content: self)
    }
}
