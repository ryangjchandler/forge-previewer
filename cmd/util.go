package cmd

import "fmt"

var (
	Stability = "dev"
	Version   = "0.0.1"
)

func GetCurrentVersion() string {
	return fmt.Sprintf("v%s-%s", Version, Stability)
}
